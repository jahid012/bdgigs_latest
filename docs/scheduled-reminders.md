# Scheduled Reminder And Automation Emails

The marketplace uses Laravel scheduler commands for delayed and recurring order, inbox, offer, and review emails. Commands should be safe to run repeatedly. Store a durable key before dispatching an event when a reminder must only be sent once.

## Commands

- `orders:send-requirement-reminders --hours=24`
  Sends `OrderRequirementsPendingReminder` for paid orders still waiting for buyer requirements.
- `orders:send-deadline-reminders`
  Sends `OrderDeadlineReminder` once for the 24 hour and 6 hour deadline windows.
- `orders:mark-overdue`
  Marks active paid orders overdue after the delivery deadline and emits `OrderOverdueAlert`.
- `custom-offers:expire`
  Marks pending or accepted expired offers as `expired` and emits `CustomOfferExpired`.
- `messages:send-unread-reminders --minutes=15`
  Sends delayed unread thread reminders only when the recipient is not active in the conversation.
- `reviews:send-deadline-reminders`
  Reminds the buyer first, then the seller after the buyer review is submitted.
- `reviews:expire-periods`
  Closes the 15 day review window and emits `ReviewPeriodExpired`.

The schedule lives in `routes/console.php`.

## Idempotency

Order reminders use `order_reminders` with a unique `order_id` and `key` pair. Use descriptive keys such as:

- `requirements_pending_24h`
- `deadline_24h`
- `deadline_6h`
- `overdue_alert`
- `review_deadline_buyer`
- `review_deadline_seller`

Inbox reminders use `messages.email_reminder_sent_at` plus `conversation_participants.last_email_reminded_at` to avoid repeating the same thread reminder too often.

## Adding A Scheduled Transactional Email

1. Create an event in `app/Events`.
2. Add or reuse a listener in `app/Listeners` that creates the database notification and calls `EmailService::queueTemplateEmail`.
3. Add the template key to `app/Support/EmailTemplateDefaults.php` and seed it through `EmailTemplateSeeder`.
4. Store a reminder key or timestamp before dispatching the event.
5. Add the command in `app/Console/Commands` and register it in `routes/console.php`.
6. Add a focused Feature test proving the command is repeat-safe and creates the email log.
