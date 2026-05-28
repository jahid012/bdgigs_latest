<?php

namespace App\Services;

use App\Events\DisputeEvidenceSubmitted;
use App\Events\DisputeOpened;
use App\Events\DisputeResponseReceived;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use App\Services\SuspiciousActivityService;

class OrderDisputeService
{
    public function __construct(
        private readonly OrderEventNotificationService $events,
        private readonly EmailService $emails,
    ) {
    }

    public function open(Order $order, User $actor, array $payload): Dispute
    {
        $this->authorizeParticipant($order, $actor);

        if ($this->activeDispute($order)) {
            throw ValidationException::withMessages([
                'reason' => 'This order already has an active resolution case.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $payload) {
            $attachments = collect($payload['attachments'] ?? [])
                ->filter(fn ($file) => $file instanceof UploadedFile)
                ->map(fn (UploadedFile $file) => $this->storeAttachment($order, $file))
                ->values()
                ->all();

            $dispute = $order->disputes()->create([
                'case_code' => $this->caseCode(),
                'opened_by_id' => $actor->id,
                'reason' => $payload['reason'],
                'description' => $payload['description'],
                'priority' => $payload['priority'] ?? 'normal',
                'status' => 'open',
                'metadata' => [
                    'source' => 'order_resolution_center',
                    'opened_by_role' => $this->participantRole($order, $actor),
                    'attachments' => $attachments,
                ],
            ]);

            $dispute->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'opened',
                'title' => 'Resolution case opened',
                'detail' => $payload['description'],
                'metadata' => [
                    'attachments' => $attachments,
                ],
            ]);

            $order->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'resolution_opened',
                'title' => 'Resolution Center case opened',
                'detail' => $actor->name.' opened '.$dispute->case_code.' for '.$payload['reason'].'.',
            ]);

            $recentDisputes = $actor->id
                ? Dispute::where('opened_by_id', $actor->id)->where('created_at', '>=', now()->subDays(7))->count()
                : 0;

            if ($recentDisputes >= 3) {
                app(SuspiciousActivityService::class)->log(
                    $actor,
                    'multiple_dispute_openings',
                    $recentDisputes >= 5 ? 'critical' : 'high',
                    'Multiple disputes were opened in a short period.',
                    ['dispute_count' => $recentDisputes],
                );
            }

            DB::afterCommit(fn () => event(new DisputeOpened($dispute->fresh(['order.buyer', 'order.seller', 'openedBy']))));

            return $dispute->fresh(['openedBy', 'activities.actor']);
        });
    }

    public function message(Order $order, Dispute $dispute, User $actor, array $payload): Dispute
    {
        $this->authorizeParticipant($order, $actor);
        $this->ensureDisputeBelongsToOrder($order, $dispute);

        if ($dispute->isTerminal()) {
            throw ValidationException::withMessages([
                'message' => 'Resolved or closed cases cannot receive new messages.',
            ]);
        }

        return DB::transaction(function () use ($order, $dispute, $actor, $payload) {
            $message = $payload['message'];

            $dispute->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'message',
                'title' => $actor->name.' added a message',
                'detail' => $message,
                'metadata' => [
                    'visibility' => 'participants',
                    'actor_role' => $this->participantRole($order, $actor),
                ],
            ]);

            $order->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'resolution_message',
                'title' => 'Resolution Center message added',
                'detail' => Str::limit($message, 180),
            ]);

            DB::afterCommit(fn () => event(new DisputeResponseReceived(
                $dispute->fresh(['order.buyer', 'order.seller', 'openedBy']),
                $actor,
                $message,
            )));

            return $dispute->refresh()->load(['openedBy', 'activities.actor']);
        });
    }

    public function evidence(Order $order, Dispute $dispute, User $actor, array $payload): Dispute
    {
        $this->authorizeParticipant($order, $actor);
        $this->ensureDisputeBelongsToOrder($order, $dispute);

        if ($dispute->isTerminal()) {
            throw ValidationException::withMessages([
                'evidence' => 'Resolved or closed cases cannot receive new evidence.',
            ]);
        }

        return DB::transaction(function () use ($order, $dispute, $actor, $payload) {
            $attachments = collect($payload['attachments'] ?? [])
                ->filter(fn ($file) => $file instanceof UploadedFile)
                ->map(fn (UploadedFile $file) => $this->storeAttachment($order, $file))
                ->values()
                ->all();
            $note = $payload['note'] ?? $payload['message'] ?? 'Evidence submitted.';

            $dispute->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'evidence_submitted',
                'title' => $actor->name.' submitted evidence',
                'detail' => $note,
                'metadata' => [
                    'attachments' => $attachments,
                    'actor_role' => $this->participantRole($order, $actor),
                ],
            ]);

            $order->activities()->create([
                'actor_id' => $actor->id,
                'type' => 'resolution_evidence_submitted',
                'title' => 'Dispute evidence submitted',
                'detail' => Str::limit($note, 180),
                'metadata' => ['dispute_id' => $dispute->id],
            ]);

            DB::afterCommit(fn () => event(new DisputeEvidenceSubmitted(
                $dispute->fresh(['order.buyer', 'order.seller', 'openedBy']),
                $actor,
                ['note' => $note, 'attachments' => $attachments],
            )));

            return $dispute->refresh()->load(['openedBy', 'activities.actor']);
        });
    }

    private function notifyOtherParticipant(Order $order, User $actor, string $type, string $title, string $detail): void
    {
        $recipient = (int) $actor->id === (int) $order->buyer_id ? $order->seller : $order->buyer;

        if (! $recipient) {
            return;
        }

        $path = (int) $recipient->id === (int) $order->seller_id
            ? '/dashboard/seller/orders/'.$order->code
            : '/dashboard/orders/'.$order->code;

        $this->events->send(
            $recipient,
            $type,
            $title,
            $detail,
            $path,
            ['orderId' => $order->code],
        );
    }

    private function activeDispute(Order $order): ?Dispute
    {
        return $order->disputes()
            ->whereNotIn('status', ['resolved', 'rejected', 'closed'])
            ->latest()
            ->first();
    }

    private function authorizeParticipant(Order $order, User $actor): void
    {
        if (
            (int) $order->buyer_id === (int) $actor->id
            || (int) $order->seller_id === (int) $actor->id
            || $actor->can('orders.manage')
        ) {
            return;
        }

        throw new AuthorizationException('You cannot manage this order resolution case.');
    }

    private function ensureDisputeBelongsToOrder(Order $order, Dispute $dispute): void
    {
        if ((int) $dispute->order_id === (int) $order->id) {
            return;
        }

        throw new AuthorizationException('This dispute does not belong to the requested order.');
    }

    private function participantRole(Order $order, User $actor): string
    {
        return (int) $order->seller_id === (int) $actor->id ? 'seller' : 'buyer';
    }

    private function caseCode(): string
    {
        do {
            $code = 'DSP-'.Str::upper(Str::random(8));
        } while (Dispute::where('case_code', $code)->exists());

        return $code;
    }

    private function storeAttachment(Order $order, UploadedFile $file): array
    {
        $directory = public_path('uploads/resolution-center/'.$order->code);
        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);
        $path = 'uploads/resolution-center/'.$order->code.'/'.$filename;

        return [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => '/'.$path,
            'mimeType' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    private function notifyAdmins(string $templateKey, array $data): void
    {
        if (! Permission::where('name', 'disputes.view')->where('guard_name', 'web')->exists()) {
            return;
        }

        User::permission('disputes.view')
            ->get()
            ->each(fn (User $admin) => $this->emails->queueTemplateEmail($templateKey, $admin, $data));
    }
}
