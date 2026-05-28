<?php

namespace App\Services;

use App\Models\User;
use App\Support\EmailTemplateDefaults;
use App\Support\MarketplaceNotifier;

class OrderEventNotificationService
{
    public function __construct(
        private readonly MarketplaceNotifier $notifier,
        private readonly EmailService $emails,
    ) {
    }

    public function send(
        User $user,
        string $type,
        string $title,
        string $detail,
        ?string $actionUrl = null,
        array $metadata = [],
        ?string $emailSubject = null
    ): void {
        $metadata = ['preferenceKey' => 'orderUpdates', ...$metadata];

        $this->notifier->notify(
            $user,
            $type,
            $title,
            $detail,
            $actionUrl,
            $metadata,
        );

        $this->emails->queueTemplateEmail(
            $metadata['emailTemplate'] ?? EmailTemplateDefaults::resolveKey($type),
            $user,
            [
                ...$metadata,
                'notification_title' => $title,
                'notification_detail' => $detail,
                'action_url' => $actionUrl ? url($actionUrl) : url('/dashboard'),
                'order_id' => $metadata['orderId'] ?? '',
                'dispute_id' => $metadata['disputeId'] ?? '',
                'custom_offer_title' => $metadata['customOfferTitle'] ?? '',
                'conversation_url' => isset($metadata['conversationId'])
                    ? url('/dashboard/messages?conversation='.$metadata['conversationId'])
                    : '',
            ],
            [
                'subject_override' => $emailSubject,
            ],
        );
    }
}
