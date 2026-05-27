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

class OrderLifecycleService
{
    public function __construct(private readonly OrderEventNotificationService $events)
    {
    }

    public function submitDelivery(Order $order, User $seller, array $payload): Order
    {
        if ((int) $order->seller_id !== (int) $seller->id) {
            throw new AuthorizationException('Only the seller can submit delivery.');
        }

        if (! in_array($this->normalizedStatus($order), ['in progress', 'revision requested'], true)) {
            throw ValidationException::withMessages([
                'message' => 'This order is not ready for delivery submission yet.',
            ]);
        }

        if (in_array($this->normalizedStatus($order), ['completed', 'cancelled', 'canceled'], true)) {
            throw ValidationException::withMessages([
                'message' => 'This order is already closed.',
            ]);
        }

        return DB::transaction(function () use ($order, $seller, $payload) {
            $metadata = $order->metadata ?: [];
            $deliveries = collect($metadata['deliveries'] ?? []);
            $delivery = [
                'id' => 'delivery-'.Str::uuid()->toString(),
                'message' => trim($payload['message']),
                'files' => collect($payload['files'] ?? [])
                    ->filter(fn ($file) => $file instanceof UploadedFile)
                    ->map(fn (UploadedFile $file) => $this->storeDeliveryFile($order, $file))
                    ->values()
                    ->all(),
                'status' => 'submitted',
                'submittedAt' => now()->toISOString(),
                'submittedBy' => $seller->id,
            ];
            $deliveries->push($delivery);
            $metadata['deliveries'] = $deliveries->values()->all();

            $order->forceFill([
                'metadata' => $metadata,
                'status' => 'Delivered',
                'status_class' => 'status-completed',
            ])->save();

            $order->activities()->create([
                'actor_id' => $seller->id,
                'type' => 'delivery_submitted',
                'title' => 'Delivery submitted',
                'detail' => $seller->name.' submitted the delivery for buyer review.',
                'metadata' => ['delivery_id' => $delivery['id']],
            ]);

            if ($order->buyer) {
                $this->events->send(
                    $order->buyer,
                    'order_delivery_submitted',
                    'Delivery submitted',
                    $seller->name.' submitted delivery for order #'.$order->code.'.',
                    '/dashboard/orders/'.$order->code,
                    ['orderId' => $order->code],
                );
            }

            return $order->refresh();
        });
    }

    public function requestRevision(Order $order, User $buyer, array $payload): Order
    {
        if ((int) $order->buyer_id !== (int) $buyer->id) {
            throw new AuthorizationException('Only the buyer can request a revision.');
        }

        if ($this->normalizedStatus($order) !== 'delivered') {
            throw ValidationException::withMessages([
                'message' => 'Revisions can only be requested after delivery.',
            ]);
        }

        return DB::transaction(function () use ($order, $buyer, $payload) {
            $metadata = $order->metadata ?: [];
            $deliveries = collect($metadata['deliveries'] ?? []);
            $latestDelivery = $deliveries->pop();

            if ($latestDelivery) {
                $latestDelivery['status'] = 'revision_requested';
                $latestDelivery['revisionRequestedAt'] = now()->toISOString();
                $latestDelivery['revisionMessage'] = trim($payload['message']);
                $deliveries->push($latestDelivery);
            }

            $metadata['deliveries'] = $deliveries->values()->all();

            $order->forceFill([
                'metadata' => $metadata,
                'status' => 'Revision Requested',
                'status_class' => 'status-delivered',
            ])->save();

            $order->activities()->create([
                'actor_id' => $buyer->id,
                'type' => 'revision_requested',
                'title' => 'Revision requested',
                'detail' => trim($payload['message']),
            ]);

            if ($order->seller) {
                $this->events->send(
                    $order->seller,
                    'order_revision_requested',
                    'Revision requested',
                    $buyer->name.' requested revisions for order #'.$order->code.'.',
                    '/dashboard/seller/orders/'.$order->code,
                    ['orderId' => $order->code],
                );
            }

            return $order->refresh();
        });
    }

    public function complete(Order $order, User $buyer): Order
    {
        if ((int) $order->buyer_id !== (int) $buyer->id) {
            throw new AuthorizationException('Only the buyer can complete this order.');
        }

        if ($this->normalizedStatus($order) !== 'delivered') {
            throw ValidationException::withMessages([
                'order' => 'Only delivered orders can be completed by the buyer.',
            ]);
        }

        return DB::transaction(function () use ($order, $buyer) {
            $metadata = $order->metadata ?: [];
            $deliveries = collect($metadata['deliveries'] ?? []);
            $latestDelivery = $deliveries->pop();

            if ($latestDelivery) {
                $latestDelivery['status'] = 'accepted';
                $latestDelivery['acceptedAt'] = now()->toISOString();
                $deliveries->push($latestDelivery);
            }

            $metadata['deliveries'] = $deliveries->values()->all();

            $order->forceFill([
                'metadata' => $metadata,
                'status' => 'Completed',
                'status_class' => 'status-completed',
            ])->save();

            $order->activities()->create([
                'actor_id' => $buyer->id,
                'type' => 'order_completed',
                'title' => 'Order completed',
                'detail' => $buyer->name.' accepted the delivery and completed the order.',
            ]);

            if ($order->seller) {
                $this->events->send(
                    $order->seller,
                    'order_completed',
                    'Order completed',
                    $buyer->name.' completed order #'.$order->code.'.',
                    '/dashboard/seller/orders/'.$order->code,
                    ['orderId' => $order->code],
                );
            }

            return $order->refresh();
        });
    }

    private function normalizedStatus(Order $order): string
    {
        return strtolower((string) $order->status);
    }

    private function storeDeliveryFile(Order $order, UploadedFile $file): array
    {
        $directory = public_path('uploads/order-deliveries/'.$order->code);
        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);
        $path = 'uploads/order-deliveries/'.$order->code.'/'.$filename;

        return [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => '/'.$path,
            'mimeType' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
