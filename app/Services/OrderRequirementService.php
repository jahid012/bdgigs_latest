<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderRequirementService
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function submit(Order $order, User $buyer, array $payload): Order
    {
        if ((int) $order->buyer_id !== (int) $buyer->id) {
            throw new AuthorizationException('Only the buyer can submit order requirements.');
        }

        if (in_array(strtolower((string) $order->status), ['completed', 'cancelled', 'canceled'], true)) {
            throw ValidationException::withMessages([
                'requirements' => 'Requirements cannot be changed after the order is closed.',
            ]);
        }

        $items = $this->requirementItems($order);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'requirements' => 'This order does not have buyer requirements to submit.',
            ]);
        }

        $answers = collect($payload['answers'] ?? []);
        $files = collect($payload['files'] ?? []);

        $prepared = $items->map(function (array $item) use ($answers, $files, $order) {
            $id = $item['id'];
            $answer = $answers->get($id, $item['answer'] ?? '');
            $storedFiles = collect($item['files'] ?? []);

            if ($files->has($id) && $files->get($id) instanceof UploadedFile) {
                $storedFiles->push($this->storeRequirementFile($order, $files->get($id)));
            }

            $answerValue = is_array($answer)
                ? collect($answer)->filter()->values()->all()
                : trim((string) $answer);

            if (($item['required'] ?? false) && $this->isMissingAnswer($item, $answerValue, $storedFiles->all())) {
                throw ValidationException::withMessages([
                    "answers.{$id}" => 'This requirement is required.',
                ]);
            }

            return [
                ...$item,
                'answer' => $answerValue,
                'files' => $storedFiles->values()->all(),
                'submittedAt' => now()->toISOString(),
            ];
        });

        return DB::transaction(function () use ($order, $buyer, $prepared) {
            $metadata = $order->metadata ?: [];
            $metadata['requirements'] = $prepared->values()->all();
            $metadata['requirementsSubmittedAt'] = now()->toISOString();
            $metadata['requirementsSubmittedBy'] = $buyer->id;

            $order->forceFill([
                'metadata' => $metadata,
                'status' => in_array(strtolower((string) $order->status), ['pending requirements', 'waiting for requirements'], true)
                    ? 'Requirements Submitted'
                    : $order->status,
                'status_class' => in_array(strtolower((string) $order->status), ['pending requirements', 'waiting for requirements'], true)
                    ? 'status-delivered'
                    : $order->status_class,
            ])->save();

            $order->activities()->create([
                'actor_id' => $buyer->id,
                'type' => 'requirements_submitted',
                'title' => 'Requirements submitted',
                'detail' => $buyer->name.' submitted the buyer requirements. The seller can now start work.',
                'metadata' => [
                    'submitted_by' => $buyer->id,
                    'requirements_count' => $prepared->count(),
                ],
            ]);

            if ($order->seller) {
                $this->events->send(
                    $order->seller,
                    'order_requirements_submitted',
                    'Order requirements submitted',
                    $buyer->name.' submitted requirements for order #'.$order->code.'.',
                    '/dashboard/seller/orders/'.$order->code,
                    ['orderId' => $order->code],
                );
            }

            $this->events->send(
                $buyer,
                'order_requirements_submitted',
                'Requirements received',
                'Your requirements for order #'.$order->code.' were submitted successfully.',
                '/dashboard/orders/'.$order->code,
                ['orderId' => $order->code],
            );

            return $order->refresh();
        });
    }

    public function requirementItems(Order $order): \Illuminate\Support\Collection
    {
        $metadataItems = collect($order->metadata['requirements'] ?? []);
        $sourceItems = $metadataItems->isNotEmpty()
            ? $metadataItems
            : collect($order->gig?->requirements ?: []);

        return $sourceItems
            ->map(fn (array $item, int $index) => $this->normalizeItem($item, $index))
            ->values();
    }

    private function normalizeItem(array $item, int $index): array
    {
        $label = $item['question'] ?? $item['label'] ?? 'Requirement';

        return [
            'id' => (string) ($item['id'] ?? Str::slug($label) ?: 'requirement-'.$index),
            'question' => $label,
            'label' => $label,
            'type' => $item['type'] ?? 'Free text',
            'required' => (bool) ($item['required'] ?? ! ($item['optional'] ?? false)),
            'optional' => ! (bool) ($item['required'] ?? ! ($item['optional'] ?? false)),
            'allowMultiple' => (bool) ($item['allowMultiple'] ?? false),
            'options' => array_values($item['options'] ?? []),
            'answer' => $item['answer'] ?? '',
            'files' => array_values($item['files'] ?? []),
            'submittedAt' => $item['submittedAt'] ?? null,
        ];
    }

    private function storeRequirementFile(Order $order, UploadedFile $file): array
    {
        $directory = public_path('uploads/order-requirements/'.$order->code);
        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);
        $path = 'uploads/order-requirements/'.$order->code.'/'.$filename;

        return [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => '/'.$path,
            'mimeType' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    private function isMissingAnswer(array $item, mixed $answer, array $files): bool
    {
        if (strtolower((string) ($item['type'] ?? '')) === 'file upload') {
            return count($files) === 0;
        }

        if (is_array($answer)) {
            return count(array_filter($answer)) === 0;
        }

        return trim((string) $answer) === '';
    }
}
