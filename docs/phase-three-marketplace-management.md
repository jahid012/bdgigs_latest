# Phase 3 Marketplace Management Notes

## Moderation And Lifecycle Events

Major Phase 3 actions update the domain record first, then dispatch Laravel events. The aggregate listener `HandlePhaseThreeMarketplaceNotification` creates database notifications, optional realtime notifications through `MarketplaceNotifier`, and PHPMailer template emails through `EmailService`.

Examples:

- Seller application: `SellerApplicationService` dispatches `SellerApplicationSubmitted`, `SellerApplicationApproved`, and `SellerApplicationRejected`.
- Gig moderation: seller/admin lifecycle services dispatch `GigCreated`, `GigSubmittedForReview`, `GigApproved`, `GigRejected`, `GigPaused`, `GigReactivated`, and `GigEdited`.
- Disputes: `OrderDisputeService` and `AdminDisputeService` dispatch dispute events and record order/dispute activities.
- Reports/security: `ModerationReportService` and `SuspiciousActivityService` dispatch moderation and security events.

To add a new moderation email:

1. Add or reuse an event in `app/Events`.
2. Add a default template key in `App\Support\EmailTemplateDefaults`.
3. Dispatch the event from a service after the state change commits.
4. Add a branch in `HandlePhaseThreeMarketplaceNotification` if no existing branch matches.
5. Seed/update templates with `php artisan db:seed --class=EmailTemplateSeeder`.

## Scheduled Email Logic

Phase 3 scheduled commands are registered in `routes/console.php`.

- `users:send-profile-completion-reminders`
- `gigs:send-performance-summary`
- `marketing:send-weekly-digest`
- `marketing:send-recently-viewed-reminders`
- `marketing:send-saved-gig-reminders`
- `marketing:send-checkout-abandonment`
- `marketing:send-reengagement-emails`

Commands are safe to rerun. Profile reminders store `profile_completion_reminded_at`; marketing campaigns write `email_campaign_logs` with a dated campaign key such as `weekly_digest:2026-22`.

## Marketing And Unsubscribe

Marketing emails use `MarketingCampaignService`, which checks `marketing_unsubscribed_at`, active campaign state, and campaign logs before queueing PHPMailer email.

Marketing templates automatically receive public preference and unsubscribe URLs from `EmailService`. Token records live in `email_preference_tokens`.

Public routes:

- `/email/preferences/{token}`
- `/email/unsubscribe/{token}`
- `/email/unsubscribe/{token}/confirm`

Unsubscribing only disables marketing email. Transactional, payment, account, and security templates continue to send because critical keys are listed in `EmailTemplateDefaults::ALWAYS_SEND_KEYS`.
