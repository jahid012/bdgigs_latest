<?php

namespace App\Providers;

use App\Events\AdminSupportMessageReceived;
use App\Events\CustomOfferExpired;
use App\Events\CustomOfferMessageReceived;
use App\Events\CustomOfferPaymentFailed;
use App\Events\MessageAttachmentReceived;
use App\Events\OrderCancellationAccepted;
use App\Events\OrderCancellationRejected;
use App\Events\OrderCancellationRequested;
use App\Events\OrderCancelled;
use App\Events\OrderDeadlineReminder;
use App\Events\OrderOverdueAlert;
use App\Events\OrderRequirementsPendingReminder;
use App\Events\ReviewDeadlineReminder;
use App\Events\ReviewPeriodExpired;
use App\Events\ReviewsVisible;
use App\Events\RevisionDelivered;
use App\Events\SellerStartedWorking;
use App\Events\UnreadMessageReminder;
use App\Listeners\AddOrderAutomationActivity;
use App\Listeners\HandleCustomOfferAutomationNotification;
use App\Listeners\HandleInboxAutomationNotification;
use App\Listeners\HandleOrderAutomationNotification;
use App\Listeners\HandlePhaseThreeMarketplaceNotification;
use App\Listeners\HandleReviewAutomationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            OrderRequirementsPendingReminder::class,
            SellerStartedWorking::class,
            OrderDeadlineReminder::class,
            OrderOverdueAlert::class,
            RevisionDelivered::class,
            OrderCancellationRequested::class,
            OrderCancellationAccepted::class,
            OrderCancellationRejected::class,
            OrderCancelled::class,
        ] as $orderAutomationEvent) {
            Event::listen($orderAutomationEvent, AddOrderAutomationActivity::class);
            Event::listen($orderAutomationEvent, HandleOrderAutomationNotification::class);
        }
        foreach ([CustomOfferExpired::class, CustomOfferPaymentFailed::class] as $customOfferEvent) {
            Event::listen($customOfferEvent, HandleCustomOfferAutomationNotification::class);
        }
        foreach ([MessageAttachmentReceived::class, CustomOfferMessageReceived::class, AdminSupportMessageReceived::class, UnreadMessageReminder::class] as $inboxEvent) {
            Event::listen($inboxEvent, HandleInboxAutomationNotification::class);
        }
        foreach ([ReviewDeadlineReminder::class, ReviewPeriodExpired::class, ReviewsVisible::class] as $reviewEvent) {
            Event::listen($reviewEvent, HandleReviewAutomationNotification::class);
        }
        foreach ([
            \App\Events\DisputeOpened::class,
            \App\Events\DisputeResponseReceived::class,
            \App\Events\DisputeAdminJoined::class,
            \App\Events\DisputeEvidenceRequested::class,
            \App\Events\DisputeEvidenceSubmitted::class,
            \App\Events\DisputeStatusUpdated::class,
            \App\Events\DisputeResolved::class,
            \App\Events\DisputeRejected::class,
            \App\Events\DisputeClosed::class,
            \App\Events\DisputeRefundIssued::class,
            \App\Events\SellerApplicationSubmitted::class,
            \App\Events\SellerApplicationApproved::class,
            \App\Events\SellerApplicationRejected::class,
            \App\Events\GigCreated::class,
            \App\Events\GigSubmittedForReview::class,
            \App\Events\GigApproved::class,
            \App\Events\GigRejected::class,
            \App\Events\GigPaused::class,
            \App\Events\GigReactivated::class,
            \App\Events\GigEdited::class,
            \App\Events\GigInquiryReceived::class,
            \App\Events\GigPerformanceSummary::class,
            \App\Events\WithdrawalRequested::class,
            \App\Events\WithdrawalAdminAlert::class,
            \App\Events\WithdrawalApproved::class,
            \App\Events\WithdrawalRejected::class,
            \App\Events\WithdrawalPaid::class,
            \App\Events\WithdrawalFailed::class,
            \App\Events\IdentityVerificationSubmitted::class,
            \App\Events\IdentityVerificationUnderReview::class,
            \App\Events\IdentityVerificationApproved::class,
            \App\Events\IdentityVerificationRejected::class,
            \App\Events\IdentityAdditionalDocumentRequested::class,
            \App\Events\IdentityDocumentUploadFailed::class,
            \App\Events\UserReported::class,
            \App\Events\GigReported::class,
            \App\Events\OrderReported::class,
            \App\Events\MessageReported::class,
            \App\Events\ReportStatusUpdated::class,
            \App\Events\SuspiciousActivityDetected::class,
            \App\Events\AdminSuspiciousActivityAlert::class,
            \App\Events\ProfileCompletionReminderDue::class,
            \App\Events\RecommendedGigsEmailDue::class,
            \App\Events\RecentlyViewedReminderDue::class,
            \App\Events\SavedGigReminderDue::class,
            \App\Events\CheckoutAbandonmentReminderDue::class,
            \App\Events\WeeklyDigestDue::class,
            \App\Events\ReEngagementEmailDue::class,
        ] as $phaseThreeEvent) {
            Event::listen($phaseThreeEvent, HandlePhaseThreeMarketplaceNotification::class);
        }

        if (method_exists(Auth::guard('web'), 'setRememberDuration')) {
            Auth::guard('web')->setRememberDuration(60 * 24 * 30);
        }

        if (method_exists(Auth::guard('admin'), 'setRememberDuration')) {
            Auth::guard('admin')->setRememberDuration(60 * 12);
        }

        RedirectResponse::macro('withNotify', function ($type = 'info', ?string $message = null, ?string $title = null, array $options = []) {
            $notification = is_array($type)
                ? $type
                : array_merge($options, [
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                ]);

            $notification['type'] = $notification['type'] ?? 'info';
            $notification['message'] = trim((string) ($notification['message'] ?? ''));

            if ($notification['message'] === '') {
                return $this;
            }

            $notifications = session()->get('notify', []);

            if (isset($notifications['message'])) {
                $notifications = [$notifications];
            }

            $notifications[] = array_filter($notification, fn ($value) => $value !== null);

            return $this->with('notify', $notifications);
        });
    }
}
