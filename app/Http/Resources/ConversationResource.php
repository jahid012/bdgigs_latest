<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use App\Models\Order;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $participants = $this->whenLoaded('participants', fn () => $this->participants, collect());
        $viewerParticipant = $participants->firstWhere('user_id', $userId)
            ?: $this->participantFor((int) $userId);
        $counterpartParticipant = $participants->first(fn ($participant) => $participant->user_id !== $userId);
        $counterpartUser = $counterpartParticipant?->user;
        $legacyName = $this->seller_id === $userId ? $this->buyer_name : $this->seller_name;
        $name = $counterpartUser?->name ?: $legacyName ?: 'Conversation';
        $lastMessage = $this->messages->last();
        $order = $this->context_type === 'order' && $this->context_id
            ? Order::where('code', ltrim($this->context_id, '#'))->first()
            : null;
        $attachments = $this->messages
            ->flatMap(fn ($message) => $message->relationLoaded('attachments') ? $message->attachments : [])
            ->values();

        return [
            'id' => $this->public_id,
            'initials' => initials($name),
            'name' => $name,
            'role' => str($counterpartParticipant?->context_role ?? 'member')->replace('_', ' ')->title()->toString(),
            'service' => $this->subject,
            'status' => $this->status,
            'statusClass' => $this->status_class,
            'time' => $lastMessage?->sent_at?->diffForHumans(short: true) ?? $this->updated_at->diffForHumans(short: true),
            'unread' => $viewerParticipant?->unread_count ?? 0,
            'priority' => $this->priority,
            'preview' => Str::limit($lastMessage?->body ?? '', 90),
            'context' => [
                'type' => $this->context_type,
                'id' => $this->context_id,
                'gigId' => $this->gig?->slug,
                'orderId' => $this->metadata['orderCode'] ?? null,
                'gig' => $this->gig ? [
                    'id' => $this->gig->slug,
                    'title' => $this->gig->title,
                    'image' => $this->gig->image,
                    'price' => '$'.number_format($this->gig->price_cents / 100, 0),
                ] : null,
                'order' => $order ? [
                    'id' => '#'.$order->code,
                    'service' => $order->service,
                    'status' => $order->status,
                    'statusClass' => $order->status_class,
                    'dueDate' => $order->due_date?->format('M j, Y'),
                    'price' => '$'.number_format($order->price_cents / 100, 0),
                ] : null,
            ],
            'counterpart' => [
                'id' => $counterpartUser?->id,
                'name' => $name,
                'initials' => initials($name),
                'username' => $counterpartUser?->username,
                'avatar' => $this->assetPath($counterpartUser?->avatar),
                'country' => $counterpartUser?->country,
                'joinedAt' => $counterpartUser?->created_at?->toISOString(),
                'online' => $counterpartUser?->last_seen_at?->greaterThan(now()->subSeconds(90)) ?? false,
                'lastSeenAt' => $counterpartUser?->last_seen_at?->toISOString(),
            ],
            'viewerParticipant' => $viewerParticipant ? [
                'contextRole' => $viewerParticipant->context_role,
                'unreadCount' => $viewerParticipant->unread_count,
                'lastReadAt' => $viewerParticipant->last_read_at?->toISOString(),
                'lastSeenAt' => $viewerParticipant->last_seen_at?->toISOString(),
                'archivedAt' => $viewerParticipant->archived_at?->toISOString(),
                'mutedAt' => $viewerParticipant->muted_at?->toISOString(),
            ] : null,
            'participants' => $participants->map(fn ($participant) => [
                'userId' => $participant->user_id,
                'name' => $participant->user?->name,
                'contextRole' => $participant->context_role,
                'unreadCount' => $participant->unread_count,
                'lastReadAt' => $participant->last_read_at?->toISOString(),
                'lastSeenAt' => $participant->last_seen_at?->toISOString(),
            ])->values(),
            'messages' => MessageResource::collection($this->whenLoaded('messages', $this->messages ?? collect())),
            'attachments' => MessageAttachmentResource::collection($attachments),
        ];
    }

    private function assetPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/') || str_starts_with($path, 'storage/')) {
            return '/'.$path;
        }

        return '/storage/'.$path;
    }
}

function initials(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn (string $part) => Str::substr($part, 0, 1))
        ->take(2)
        ->implode('');
}
