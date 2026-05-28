<?php

namespace App\Listeners;

use App\Events\MarketplaceEmailRequested;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMarketplaceEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function viaQueue(): string
    {
        return 'emails';
    }

    public function handle(MarketplaceEmailRequested $event): void
    {
        $user = User::find($event->userId);

        if (! $user || blank($user->email)) {
            return;
        }

        app(EmailService::class)->sendTemplateEmail($event->templateKey, $user, $event->data, $event->options);
    }

    public function failed(MarketplaceEmailRequested $event, \Throwable $exception): void
    {
        Log::warning('Queued marketplace email job failed.', [
            'user_id' => $event->userId,
            'template_key' => $event->templateKey,
            'error' => $exception->getMessage(),
        ]);
    }
}
