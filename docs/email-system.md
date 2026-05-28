# BDGigs Email System

BDGigs sends marketplace and transactional emails through `App\Services\EmailService`, which wraps PHPMailer, renders admin-managed templates, and records every send attempt in `email_logs`.

Controllers and services should dispatch domain events. Listeners then fan out to email, database notifications, realtime notification events where available, and order activity records.

## Main Pieces

- `email_templates`: admin-managed subject, HTML, text, category, active flag, and available variables.
- `email_logs`: pending, sent, failed, and retried send records with recipient, subject, payload, rendered body, and error details.
- `user_email_preferences`: normalized category preferences synced from `/dashboard/settings/notifications`.
- `App\Support\EmailTemplateDefaults`: source of default template keys, variables, categories, and starter content.
- `EmailTemplateSeeder`: installs or refreshes default marketplace templates and is safe to rerun.
- `EmailService`: queues template sends, renders templates, applies preference rules, calls PHPMailer, and writes logs.
- `MarketplaceEmailRequested`: generic event for template sends that do not need their own domain event.
- `SendMarketplaceEmail`: queued listener that calls `EmailService`.
- `/admin/email-templates`: admin template editing, preview, reset, and test-send UI.
- `/admin/email-logs`: production email log search, detail, rendered preview, payload view, error view, and failed-email retry.

## Configuration

Use the normal mail environment values. For production PHPMailer SMTP:

```env
MAIL_MAILER=phpmailer
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@bdgigs.com
MAIL_FROM_NAME="BDGigs"
```

`MAIL_MAILER=log` remains useful locally; the service renders the same email and writes it to Laravel logs without touching SMTP. Tests use the array mailer and sync queue.

## Transactional Flow Pattern

Use a domain event for important lifecycle actions:

```txt
app/Events/OrderPaymentSuccessful.php
app/Listeners/SendOrderPaymentSuccessfulEmail.php
app/Listeners/CreateOrderPaymentSuccessfulNotification.php
app/Listeners/AddOrderPaymentSuccessfulActivity.php
```

Register listeners in `App\Providers\AppServiceProvider`. Order-related listeners should add timeline records. User-facing listeners should use `MarketplaceNotifier` for database and realtime-ready notifications. Email listeners should call `EmailService::queueTemplateEmail(...)` so PHPMailer, preferences, rendering, and email logs stay centralized.

Current Phase 1 events include:

- `EmailVerificationRequested`
- `EmailVerified`
- `PasswordResetRequested`
- `PasswordChanged`
- `NewDeviceLoginDetected`
- `AccountSuspended`
- `AccountReactivated`
- `AccountDeactivated`
- `OrderPlaced`
- `OrderPaymentSuccessful`
- `OrderPaymentFailed`
- `OrderRefunded`
- `OrderInvoiceGenerated`

## Sending A Marketplace Email

For a simple template-only email, dispatch the generic request event:

```php
use App\Events\MarketplaceEmailRequested;

event(new MarketplaceEmailRequested($user->id, 'new_order_created', [
    'order_id' => $order->code,
    'order_title' => $order->service,
    'order_amount' => '$'.number_format($order->price_cents / 100, 2),
    'action_url' => '/dashboard/seller/orders/'.$order->code,
]));
```

For a core transactional flow, prefer a specific domain event plus listeners:

```php
event(new OrderPaymentSuccessful($order, $transactionId, 'wallet'));
```

Then keep each concern in its own listener:

- Send the template email.
- Create a database notification.
- Broadcast or rely on existing realtime-ready notification hooks.
- Add order activity when the event changes an order.
- Let `EmailService` create the email log.

## Adding A New Transactional Email

1. Add the template key, category, subject, body, and variables in `App\Support\EmailTemplateDefaults::definitions()`.
2. Run `php artisan db:seed --class=EmailTemplateSeeder`.
3. Create a domain event in `app/Events` when the action is important enough to affect more than email.
4. Add focused listeners in `app/Listeners`, for example `Send...Email`, `Create...Notification`, and `Add...Activity`.
5. Register the listeners in `App\Providers\AppServiceProvider`.
6. Trigger the event from the relevant service, not directly from the controller.
7. Use `EmailService::queueTemplateEmail($key, $user, $payload)` inside the email listener.
8. If the email belongs to a new preference category, add the mapping in `NotificationPreferenceService` and the dashboard notification settings rows.
9. Test the template from `/admin/email-templates`, then verify the send and log from `/admin/email-logs`.

Security, password, core payment, order, dispute, and account status emails are treated as critical and ignore opt-outs. Marketing and engagement templates respect user preferences.

## Email Log Retry

Admins can open `/admin/email-logs/{id}` to inspect the original payload, rendered preview, status, and error message. Failed template emails can be retried from the detail page. Retry sends use the same template key and payload, then create a fresh email log entry so the original failure remains auditable.

## Receipt And Invoice Emails

`OrderInvoiceService` creates durable invoice rows with receipt payload data such as buyer, seller, service title, amount, platform fee, payment method, transaction ID, date, and platform name. `OrderInvoiceGenerated` sends the `invoice_receipt_email` template. Buyers can view receipts from the dashboard through the order receipt URL.
