<?php

namespace App\Listeners;

use App\Events\AdminSuspiciousActivityAlert;
use App\Events\CheckoutAbandonmentReminderDue;
use App\Events\DisputeAdminJoined;
use App\Events\DisputeClosed;
use App\Events\DisputeEvidenceRequested;
use App\Events\DisputeEvidenceSubmitted;
use App\Events\DisputeOpened;
use App\Events\DisputeRefundIssued;
use App\Events\DisputeRejected;
use App\Events\DisputeResolved;
use App\Events\DisputeResponseReceived;
use App\Events\DisputeStatusUpdated;
use App\Events\GigApproved;
use App\Events\GigCreated;
use App\Events\GigEdited;
use App\Events\GigInquiryReceived;
use App\Events\GigPaused;
use App\Events\GigPerformanceSummary;
use App\Events\GigReactivated;
use App\Events\GigRejected;
use App\Events\GigReported;
use App\Events\GigSubmittedForReview;
use App\Events\IdentityAdditionalDocumentRequested;
use App\Events\IdentityDocumentUploadFailed;
use App\Events\IdentityVerificationApproved;
use App\Events\IdentityVerificationRejected;
use App\Events\IdentityVerificationSubmitted;
use App\Events\IdentityVerificationUnderReview;
use App\Events\MessageReported;
use App\Events\OrderReported;
use App\Events\ProfileCompletionReminderDue;
use App\Events\RecentlyViewedReminderDue;
use App\Events\RecommendedGigsEmailDue;
use App\Events\ReEngagementEmailDue;
use App\Events\ReportStatusUpdated;
use App\Events\SavedGigReminderDue;
use App\Events\SellerApplicationApproved;
use App\Events\SellerApplicationRejected;
use App\Events\SellerApplicationSubmitted;
use App\Events\SuspiciousActivityDetected;
use App\Events\UserReported;
use App\Events\WeeklyDigestDue;
use App\Events\WithdrawalAdminAlert;
use App\Events\WithdrawalApproved;
use App\Events\WithdrawalFailed;
use App\Events\WithdrawalPaid;
use App\Events\WithdrawalRejected;
use App\Events\WithdrawalRequested;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Dispute;
use App\Models\Gig;
use App\Models\ModerationReport;
use App\Models\Order;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\EmailService;
use App\Services\MarketingCampaignService;
use App\Support\MarketplaceNotifier;
use Spatie\Permission\Models\Permission;

class HandlePhaseThreeMarketplaceNotification
{
    public function __construct(
        private readonly MarketplaceNotifier $notifier,
        private readonly EmailService $emails,
        private readonly MarketingCampaignService $campaigns,
    ) {
    }

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof SellerApplicationSubmitted => $this->sellerSubmitted($event),
            $event instanceof SellerApplicationApproved => $this->sellerDecision($event->seller, 'seller_account_approval', 'Seller account approved', 'Your seller application was approved.', '/dashboard/seller'),
            $event instanceof SellerApplicationRejected => $this->sellerDecision($event->seller, 'seller_account_rejection', 'Seller account rejected', $event->reason ?: 'Your seller application needs changes.', '/dashboard/seller/settings'),

            $event instanceof GigCreated => $this->gigSeller($event->gig, 'gig_created_successfully', 'Gig draft saved', 'Your gig draft was saved.'),
            $event instanceof GigSubmittedForReview => $this->gigSubmitted($event->gig),
            $event instanceof GigApproved => $this->gigSeller($event->gig, 'gig_approved', 'Gig approved', 'Your gig was approved and is eligible for the marketplace.'),
            $event instanceof GigRejected => $this->gigSeller($event->gig, 'gig_rejected', 'Gig rejected', $event->reason ?: 'Your gig needs changes before approval.'),
            $event instanceof GigPaused => $this->gigSeller($event->gig, 'gig_paused_deactivated', 'Gig paused', $event->reason ?: 'Your gig was paused.'),
            $event instanceof GigReactivated => $this->gigSeller($event->gig, 'gig_reactivated', 'Gig reactivated', 'Your gig is active again.'),
            $event instanceof GigEdited => $this->gigSeller($event->gig, 'gig_edited_successfully', 'Gig updated', 'Your gig changes were saved.'),
            $event instanceof GigInquiryReceived => $this->gigInquiry($event->gig),
            $event instanceof GigPerformanceSummary => $this->notifyUser($event->seller, 'gig_performance_summary', 'Gig performance summary', 'Your latest gig performance summary is ready.', '/dashboard/seller/services', $event->summary),

            $event instanceof WithdrawalRequested => $this->withdrawalSeller($event->withdrawal, 'withdrawal_request_submitted', 'Withdrawal request submitted', 'Your withdrawal is waiting for finance review.'),
            $event instanceof WithdrawalAdminAlert => $this->withdrawalAdmins($event->withdrawal),
            $event instanceof WithdrawalApproved => $this->withdrawalSeller($event->withdrawal, 'withdrawal_approved', 'Withdrawal approved', 'Your withdrawal was approved.'),
            $event instanceof WithdrawalRejected => $this->withdrawalSeller($event->withdrawal, 'withdrawal_rejected', 'Withdrawal rejected', 'Your withdrawal was rejected.'),
            $event instanceof WithdrawalPaid => $this->withdrawalSeller($event->withdrawal, 'withdrawal_paid_sent', 'Withdrawal paid', 'Your withdrawal has been marked paid.'),
            $event instanceof WithdrawalFailed => $this->withdrawalSeller($event->withdrawal, 'withdrawal_status_updated', 'Withdrawal failed', $event->reason ?: 'Your withdrawal failed.'),

            $event instanceof IdentityVerificationSubmitted => $this->identitySubmitted($event),
            $event instanceof IdentityVerificationUnderReview => $this->identityUser($event->submission, 'identity_verification_under_review', 'Verification under review', 'Your identity documents are under manual review.'),
            $event instanceof IdentityVerificationApproved => $this->identityUser($event->submission, 'identity_verification_approved', 'Verification approved', 'Your identity verification was approved.'),
            $event instanceof IdentityVerificationRejected => $this->identityUser($event->submission, 'identity_verification_rejected', 'Verification rejected', $event->reason ?: 'Your identity verification needs changes.'),
            $event instanceof IdentityAdditionalDocumentRequested => $this->identityUser($event->submission, 'additional_document_requested', 'Additional document requested', $event->note ?: 'Please upload an additional identity document.'),
            $event instanceof IdentityDocumentUploadFailed => $this->notifyUser($event->user, 'document_upload_failed_expired', 'Document upload failed', $event->reason ?: 'Your identity document upload failed.', '/dashboard/seller/settings/identity-verification'),

            $event instanceof UserReported => $this->reportCreated($event->report, 'user_reported', 'User report received'),
            $event instanceof GigReported => $this->reportCreated($event->report, 'gig_reported', 'Gig report received'),
            $event instanceof OrderReported => $this->reportCreated($event->report, 'order_reported', 'Order report received'),
            $event instanceof MessageReported => $this->reportCreated($event->report, 'message_reported', 'Message report received'),
            $event instanceof ReportStatusUpdated => $this->reportUpdated($event->report),

            $event instanceof SuspiciousActivityDetected => $this->suspiciousUser($event),
            $event instanceof AdminSuspiciousActivityAlert => $this->suspiciousAdmins($event),
            $event instanceof ProfileCompletionReminderDue => $this->profileReminder($event),

            $event instanceof DisputeOpened => $this->disputeOpened($event->dispute),
            $event instanceof DisputeResponseReceived => $this->disputeResponse($event->dispute, $event->actor, $event->message),
            $event instanceof DisputeAdminJoined => $this->disputeParticipants($event->dispute, 'admin_joined_dispute', 'Support joined your dispute', 'An admin joined the Resolution Center case.'),
            $event instanceof DisputeEvidenceRequested => $this->disputeEvidenceRequested($event),
            $event instanceof DisputeEvidenceSubmitted => $this->disputeParticipants($event->dispute, 'dispute_evidence_submitted', 'Evidence submitted', 'New evidence was added to the Resolution Center case.'),
            $event instanceof DisputeStatusUpdated => $this->disputeParticipants($event->dispute, 'dispute_updated', 'Resolution Center updated', 'The case status changed from '.$event->previousStatus.' to '.$event->dispute->status.'.'),
            $event instanceof DisputeResolved => $this->disputeParticipants($event->dispute, 'dispute_resolved', 'Dispute resolved', 'The Resolution Center case was resolved.'),
            $event instanceof DisputeRejected => $this->disputeParticipants($event->dispute, 'dispute_rejected_closed', 'Dispute rejected', 'The Resolution Center case was rejected.'),
            $event instanceof DisputeClosed => $this->disputeParticipants($event->dispute, 'dispute_rejected_closed', 'Dispute closed', 'The Resolution Center case was closed.'),
            $event instanceof DisputeRefundIssued => $this->disputeRefund($event),

            $event instanceof RecommendedGigsEmailDue => $this->campaigns->send($event->user, 'recommended_gigs:'.now()->format('Y-m-d'), 'recommended_gigs_for_buyer', $event->payload),
            $event instanceof RecentlyViewedReminderDue => $this->campaigns->send($event->user, 'recently_viewed:'.now()->format('Y-m-d'), 'recently_viewed_gigs_reminder', $event->payload),
            $event instanceof SavedGigReminderDue => $this->campaigns->send($event->user, 'saved_gig:'.now()->format('Y-m-d'), 'saved_favorite_gig_reminder', $event->payload),
            $event instanceof CheckoutAbandonmentReminderDue => $this->campaigns->send($event->user, 'checkout_abandonment:'.now()->format('Y-m-d'), 'incomplete_order_checkout_reminder', $event->payload),
            $event instanceof WeeklyDigestDue => $this->campaigns->send($event->user, 'weekly_digest:'.now()->format('o-W'), 'weekly_marketplace_digest', $event->payload),
            $event instanceof ReEngagementEmailDue => $this->campaigns->send($event->user, 're_engagement:'.now()->format('Y-m-d'), 're_engagement_email_for_inactive_users', $event->payload),
            default => null,
        };
    }

    private function sellerSubmitted(SellerApplicationSubmitted $event): void
    {
        $this->notifyUser($event->seller, 'seller_application_submitted', 'Seller application submitted', 'Your seller application is waiting for review.', '/dashboard/seller/settings');
        $this->notifyAdmins('users.verify', 'seller_verification_submitted', 'Seller application submitted', $event->seller->name.' submitted a seller application.', route('admin.seller-applications.show', $event->seller, false));
    }

    private function sellerDecision(User $seller, string $template, string $title, string $detail, string $url): void
    {
        $this->notifyUser($seller, $template, $title, $detail, $url, [
            'notification_detail' => $seller->seller_status_reason ?: $detail,
        ]);
    }

    private function gigSubmitted(Gig $gig): void
    {
        $this->gigSeller($gig, 'gig_submitted_for_review', 'Gig submitted for review', 'Your gig is waiting for marketplace review.');
        $this->notifyAdmins('gigs.review', 'gig_submitted_for_review', 'Gig submitted for review', ($gig->seller?->name ?: 'A seller').' submitted '.$gig->title.'.', route('admin.gigs.show', $gig, false));
    }

    private function gigSeller(Gig $gig, string $template, string $title, string $detail): void
    {
        $gig->loadMissing('seller');

        if (! $gig->seller) {
            return;
        }

        $this->notifyUser($gig->seller, $template, $title, $detail, '/dashboard/seller/services/'.$gig->slug.'/edit', [
            'gig_title' => $gig->title,
            'notification_detail' => $gig->moderation_reason ?: $detail,
        ]);
    }

    private function gigInquiry(Gig $gig): void
    {
        $gig->loadMissing('seller');
        $gig->seller && $this->notifyUser($gig->seller, 'gig_received_inquiry_message', 'New gig inquiry', 'A buyer sent a message about '.$gig->title.'.', '/dashboard/seller/messages', [
            'gig_title' => $gig->title,
        ]);
    }

    private function withdrawalSeller(WithdrawalRequest $withdrawal, string $template, string $title, string $detail): void
    {
        $withdrawal->loadMissing('seller');

        if (! $withdrawal->seller) {
            return;
        }

        $amount = '$'.number_format(($withdrawal->approved_amount_cents ?: $withdrawal->amount_cents) / 100, 2);
        $this->notifyUser($withdrawal->seller, $template, $title, $withdrawal->code.': '.$detail, '/dashboard/seller/earnings', [
            'withdrawal_id' => $withdrawal->code,
            'order_amount' => $amount,
        ]);
    }

    private function withdrawalAdmins(WithdrawalRequest $withdrawal): void
    {
        $withdrawal->loadMissing('seller');
        $amount = '$'.number_format($withdrawal->amount_cents / 100, 2);
        $this->notifyAdmins('withdrawals.view', 'new_withdrawal_request_alert_to_admin', 'New withdrawal request', ($withdrawal->seller?->name ?: 'Seller').' requested '.$amount.'.', route('admin.withdrawals', [], false), [
            'withdrawal_id' => $withdrawal->code,
            'order_amount' => $amount,
        ]);
    }

    private function identitySubmitted(IdentityVerificationSubmitted $event): void
    {
        $submission = $event->submission->loadMissing('user');

        $this->identityUser($submission, 'identity_verification_submitted', 'Verification submitted', 'Your identity document was uploaded and queued for review.');

        if ($submission->user) {
            $this->notifyAdmins('users.verify', 'seller_verification_submitted', 'Identity verification submitted', $submission->user->name.' submitted identity verification.', route('admin.users.show', $submission->user, false));
        }
    }

    private function identityUser($submission, string $template, string $title, string $detail): void
    {
        $submission->loadMissing('user');

        if (! $submission->user) {
            return;
        }

        $this->notifyUser($submission->user, $template, $title, $detail, '/dashboard/seller/settings/identity-verification', [
            'notification_detail' => $submission->review_note ?: $submission->additional_document_note ?: $detail,
        ]);
    }

    private function reportCreated(ModerationReport $report, string $template, string $title): void
    {
        $detail = 'Report '.$report->code.' requires moderation review.';
        $this->notifyAdmins('reports.view', $template, $title, $detail, route('admin.moderation-reports.show', $report, false), [
            'notification_detail' => $report->reason,
        ]);
    }

    private function reportUpdated(ModerationReport $report): void
    {
        $report->loadMissing('reporter');

        if (! $report->reporter) {
            return;
        }

        $this->notifyUser($report->reporter, 'report_status_updated', 'Report status updated', 'Your report '.$report->code.' is now '.$report->status.'.', '/dashboard', [
            'notification_detail' => $report->resolution_note ?: 'Your report was reviewed by marketplace support.',
        ]);
    }

    private function suspiciousUser(SuspiciousActivityDetected $event): void
    {
        $activity = $event->activity->loadMissing('user');

        if (! $activity->user || in_array($activity->severity, ['low', 'medium'], true)) {
            return;
        }

        $this->notifyUser($activity->user, 'suspicious_activity_user_alert', 'Suspicious activity detected', $activity->description, '/dashboard/settings/security', [
            'notification_detail' => $activity->description,
        ]);
    }

    private function suspiciousAdmins(AdminSuspiciousActivityAlert $event): void
    {
        $activity = $event->activity->loadMissing('user');
        $this->notifyAdmins('security.view', 'suspicious_activity_alert', 'Suspicious activity alert', $activity->description, route('admin.suspicious-activities.show', $activity, false), [
            'notification_detail' => $activity->description,
        ]);
    }

    private function profileReminder(ProfileCompletionReminderDue $event): void
    {
        $detail = 'Missing: '.implode(', ', $event->missing ?: ['profile details']).'.';
        $this->notifyUser($event->user, 'profile_completion_reminder', 'Complete your profile', $detail, '/dashboard/profile', [
            'notification_detail' => $detail,
        ]);
        $event->user->forceFill(['profile_completion_reminded_at' => now()])->save();
    }

    private function disputeOpened(Dispute $dispute): void
    {
        $this->disputeParticipants($dispute, 'dispute_opened', 'Resolution Center case opened', 'A Resolution Center case was opened.');
        $this->notifyAdmins('disputes.view', 'new_dispute_alert_to_admin', 'New dispute opened', 'Dispute '.$dispute->case_code.' needs review.', route('admin.disputes.show', $dispute, false), [
            'dispute_id' => $dispute->case_code,
            'order_id' => $dispute->order?->code,
        ]);
    }

    private function disputeResponse(Dispute $dispute, User $actor, string $message): void
    {
        $dispute->loadMissing('order.buyer', 'order.seller');
        foreach ($this->disputeUsers($dispute) as $user) {
            if ((int) $user->id === (int) $actor->id) {
                continue;
            }

            $this->notifyDisputeUser($dispute, $user, 'dispute_response_received', 'New dispute response', str($message)->limit(180)->toString());
        }
    }

    private function disputeEvidenceRequested(DisputeEvidenceRequested $event): void
    {
        if ($event->recipient) {
            $this->notifyDisputeUser($event->dispute, $event->recipient, 'dispute_evidence_requested', 'Evidence requested', $event->note);

            return;
        }

        $this->disputeParticipants($event->dispute, 'dispute_evidence_requested', 'Evidence requested', $event->note);
    }

    private function disputeRefund(DisputeRefundIssued $event): void
    {
        $detail = 'A refund of $'.number_format($event->amountCents / 100, 2).' was issued from dispute '.$event->dispute->case_code.'.';
        $this->disputeParticipants($event->dispute, 'refund_issued_from_dispute', 'Refund issued', $detail);
    }

    private function disputeParticipants(Dispute $dispute, string $template, string $title, string $detail): void
    {
        $dispute->loadMissing('order.buyer', 'order.seller');

        foreach ($this->disputeUsers($dispute) as $user) {
            $this->notifyDisputeUser($dispute, $user, $template, $title, $detail);
        }
    }

    private function notifyDisputeUser(Dispute $dispute, User $user, string $template, string $title, string $detail): void
    {
        $isSeller = (int) $user->id === (int) $dispute->order?->seller_id;
        $url = ($isSeller ? '/dashboard/seller/orders/' : '/dashboard/orders/').$dispute->order?->code;

        $this->notifyUser($user, $template, $title, $detail, $url, [
            'order_id' => $dispute->order?->code,
            'dispute_id' => $dispute->case_code,
        ]);
    }

    private function disputeUsers(Dispute $dispute): array
    {
        return collect([$dispute->order?->buyer, $dispute->order?->seller])
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }

    private function notifyUser(User $user, string $template, string $title, string $detail, string $url, array $data = []): void
    {
        $this->notifier->notify($user, $template, $title, $detail, $url, [
            'preferenceKey' => $data['preferenceKey'] ?? null,
            ...$data,
        ]);

        $this->emails->queueTemplateEmail($template, $user, [
            'notification_title' => $title,
            'notification_detail' => $data['notification_detail'] ?? $detail,
            'action_url' => $url,
            ...$data,
        ]);
    }

    private function notifyAdmins(string $permission, string $template, string $title, string $detail, string $url, array $data = []): void
    {
        if (! Permission::where('name', $permission)->where('guard_name', 'admin')->exists()) {
            return;
        }

        Admin::permission($permission)
            ->get()
            ->each(fn (Admin $admin) => $this->notifyAdmin($admin, $template, $title, $detail, $url, $data));
    }

    private function notifyAdmin(Admin $admin, string $template, string $title, string $detail, string $url, array $data = []): void
    {
        AdminNotification::create([
            'admin_id' => $admin->id,
            'type' => $template,
            'title' => $title,
            'detail' => $detail,
            'action_url' => $url,
            'metadata' => $data,
        ]);
    }
}
