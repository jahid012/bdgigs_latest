<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignLog;
use App\Models\User;
use Illuminate\Support\Str;

class MarketingCampaignService
{
    public function __construct(private readonly EmailService $emails)
    {
    }

    public function send(User $user, string $campaignKey, string $templateKey, array $payload = []): bool
    {
        if ($user->marketing_unsubscribed_at) {
            return false;
        }

        $campaign = EmailCampaign::firstOrCreate(
            ['key' => Str::beforeLast($campaignKey, ':')],
            [
                'name' => str($campaignKey)->beforeLast(':')->replace(['_', '-'], ' ')->title()->toString(),
                'email_template_key' => $templateKey,
                'category' => 'marketing',
                'is_active' => true,
            ],
        );

        if (! $campaign->is_active) {
            return false;
        }

        $log = EmailCampaignLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'campaign_key' => $campaignKey,
                'email_template_key' => $templateKey,
            ],
            [
                'email_campaign_id' => $campaign->id,
                'status' => 'queued',
                'sent_at' => now(),
                'metadata' => $payload,
            ],
        );

        if (! $log->wasRecentlyCreated) {
            return false;
        }

        $this->emails->queueTemplateEmail($templateKey, $user, [
            'email_type' => 'marketing',
            'action_url' => $payload['action_url'] ?? '/dashboard',
            'notification_detail' => $payload['notification_detail'] ?? 'You have new marketplace activity waiting.',
            ...$payload,
        ]);

        return true;
    }
}
