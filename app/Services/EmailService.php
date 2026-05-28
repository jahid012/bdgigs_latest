<?php

namespace App\Services;

use App\Events\MarketplaceEmailRequested;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\EmailTemplateDefaults;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences,
        private readonly EmailPreferenceTokenService $tokens,
    ) {
    }

    public function queueTemplateEmail(string $templateKey, User $user, array $data = [], array $options = []): void
    {
        event(new MarketplaceEmailRequested($user->id, $templateKey, $data, $options));
    }

    public function sendTemplateEmail(string $templateKey, User $user, array $data = [], array $options = []): bool
    {
        $template = $this->template($templateKey);

        if (! $template) {
            return $this->failWithoutTemplate($templateKey, $user, $data);
        }

        if (! $template->is_active) {
            Log::info('Email template is inactive; email skipped.', [
                'template_key' => $template->key,
                'user_id' => $user->id,
            ]);

            return false;
        }

        if (! ($options['force'] ?? false) && ! $this->shouldSendEmail($user, $template->category, $template->key)) {
            return false;
        }

        $payload = $this->payloadForUser($user, $data, [
            ...$options,
            'template_category' => $template->category,
        ]);
        $subject = $this->renderTemplate($options['subject_override'] ?? $template->subject, $payload, false);
        $body = $this->renderTemplate($template->html_body, $payload);
        $textBody = $this->renderTemplate($template->text_body ?: $this->plainText($body), $payload, false);
        $html = view('emails.layouts.base', [
            'content' => $body,
            'preheader' => $this->preheader($textBody),
            'platformName' => $payload['platform_name'],
            'logoUrl' => url('/assets/img/logo.png'),
            'supportUrl' => $payload['support_url'],
            'preferencesUrl' => $payload['preferences_url'],
            'unsubscribeUrl' => $payload['unsubscribe_url'] ?? null,
        ])->render();

        return $this->sendRawEmail(
            $user->email,
            $subject,
            $html,
            $textBody,
            [
                ...$options,
                'user_id' => $user->id,
                'template_key' => $template->key,
                'recipient_name' => $user->name,
                'payload' => Arr::except($payload, ['password', 'token']),
            ],
        );
    }

    public function sendRawEmail(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $options = []
    ): bool {
        if (blank($to)) {
            return false;
        }

        $log = $this->logEmail([
            'user_id' => $options['user_id'] ?? null,
            'email_template_key' => $options['template_key'] ?? null,
            'recipient_email' => $to,
            'subject' => $subject,
            'status' => 'pending',
            'payload' => $options['payload'] ?? [],
        ]);

        try {
            if ($this->usesLogTransport()) {
                Log::channel(config('mail.mailers.log.channel') ?: config('logging.default'))
                    ->info('PHPMailer email rendered.', [
                        'to' => $to,
                        'subject' => $subject,
                        'html' => $htmlBody,
                        'text' => $textBody,
                    ]);
            } else {
                $this->sendWithPhpMailer($to, $subject, $htmlBody, $textBody, $options);
            }

            $this->markLog($log, 'sent');

            return true;
        } catch (\Throwable $exception) {
            $this->markLog($log, 'failed', $exception->getMessage());
            Log::warning('PHPMailer email could not be sent.', [
                'to' => $to,
                'subject' => $subject,
                'template_key' => $options['template_key'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function renderTemplate(string $template, array $data, bool $escapeHtml = true): string
    {
        return preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($data, $escapeHtml) {
            $value = data_get($data, $matches[1], '');

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            $value = (string) $value;

            return $escapeHtml ? e($value) : $value;
        }, $template) ?? '';
    }

    public function shouldSendEmail(User $user, string $category, string $templateKey): bool
    {
        if (in_array($templateKey, EmailTemplateDefaults::ALWAYS_SEND_KEYS, true) || $category === 'security') {
            return true;
        }

        $normalizedCategory = Str::snake($category);

        if ($normalizedCategory === 'marketing' && $user->marketing_unsubscribed_at) {
            return false;
        }

        $explicit = $user->emailPreferences()
            ->where('email_type', $normalizedCategory)
            ->first();

        if ($explicit) {
            return $explicit->is_enabled;
        }

        $legacyKey = $this->legacyPreferenceKey($normalizedCategory);

        return $legacyKey
            ? $this->preferences->allowsEmail($user, $legacyKey)
            : true;
    }

    public function logEmail(array $payload): ?EmailLog
    {
        if (! Schema::hasTable('email_logs')) {
            return null;
        }

        return EmailLog::create($payload);
    }

    public function preview(string $templateKey, array $data = []): array
    {
        $template = $this->template($templateKey);

        if (! $template) {
            return ['subject' => '', 'html' => '', 'text' => ''];
        }

        $payload = $this->payloadForUser(auth()->user() ?: new User(['name' => 'Admin', 'email' => config('mail.from.address')]), $data, []);
        $body = $this->renderTemplate($template->html_body, $payload);
        $text = $this->renderTemplate($template->text_body ?: $this->plainText($body), $payload, false);

        return [
            'subject' => $this->renderTemplate($template->subject, $payload, false),
            'html' => view('emails.layouts.base', [
                'content' => $body,
                'preheader' => $this->preheader($text),
                'platformName' => $payload['platform_name'],
                'logoUrl' => url('/assets/img/logo.png'),
                'supportUrl' => $payload['support_url'],
                'preferencesUrl' => $payload['preferences_url'],
                'unsubscribeUrl' => $payload['unsubscribe_url'] ?? null,
            ])->render(),
            'text' => $text,
        ];
    }

    private function template(string $templateKey): ?EmailTemplate
    {
        $key = EmailTemplateDefaults::resolveKey($templateKey);

        if (Schema::hasTable('email_templates')) {
            $template = EmailTemplate::where('key', $key)->first();

            if ($template) {
                return $template;
            }
        }

        $default = EmailTemplateDefaults::get($key);

        return $default ? new EmailTemplate($default) : null;
    }

    private function sendWithPhpMailer(string $to, string $subject, string $htmlBody, ?string $textBody, array $options): void
    {
        $mailer = new PHPMailer(true);
        $config = $this->mailerConfig();
        $encryption = strtolower((string) ($config['encryption'] ?? env('MAIL_ENCRYPTION', env('MAIL_SCHEME', ''))));

        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->isSMTP();
        $mailer->Host = (string) ($config['host'] ?? '127.0.0.1');
        $mailer->Port = (int) ($config['port'] ?? 587);
        $mailer->SMTPAuth = filled($config['username'] ?? null);
        $mailer->Username = (string) ($config['username'] ?? '');
        $mailer->Password = (string) ($config['password'] ?? '');
        $mailer->Timeout = (int) ($config['timeout'] ?? 30);
        $mailer->SMTPDebug = app()->environment('local') && (bool) ($config['debug'] ?? false)
            ? SMTP::DEBUG_SERVER
            : SMTP::DEBUG_OFF;

        if (in_array($encryption, ['tls', 'starttls'], true)) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (in_array($encryption, ['ssl', 'smtps'], true)) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $from = config('mail.from.address');
        $fromName = config('mail.from.name', config('app.name'));
        $mailer->setFrom($from, $fromName);

        if (filled($options['reply_to'] ?? null)) {
            $mailer->addReplyTo($options['reply_to'], $options['reply_to_name'] ?? '');
        } elseif (filled($config['reply_to'] ?? null)) {
            $mailer->addReplyTo($config['reply_to'], $config['reply_to_name'] ?? $fromName);
        }

        $mailer->addAddress($to, $options['recipient_name'] ?? '');
        $this->addRecipients($mailer, 'addCC', $options['cc'] ?? []);
        $this->addRecipients($mailer, 'addBCC', $options['bcc'] ?? []);
        $this->addAttachments($mailer, $options['attachments'] ?? []);

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody ?: $this->plainText($htmlBody);
        $mailer->send();
    }

    private function addRecipients(PHPMailer $mailer, string $method, mixed $recipients): void
    {
        foreach (Arr::wrap($recipients) as $email => $name) {
            if (is_int($email)) {
                $email = $name;
                $name = '';
            }

            if (filled($email)) {
                $mailer->{$method}((string) $email, (string) $name);
            }
        }
    }

    private function addAttachments(PHPMailer $mailer, mixed $attachments): void
    {
        foreach (Arr::wrap($attachments) as $attachment) {
            $path = is_array($attachment) ? ($attachment['path'] ?? null) : $attachment;

            if (! is_string($path) || ! is_readable($path)) {
                continue;
            }

            $mailer->addAttachment($path, is_array($attachment) ? ($attachment['name'] ?? '') : '');
        }
    }

    private function payloadForUser(User $user, array $data, array $options): array
    {
        $actionUrl = $data['action_url'] ?? $options['action_url'] ?? $data['order_url'] ?? $data['conversation_url'] ?? '/dashboard';
        $emailType = $data['email_type'] ?? $options['email_type'] ?? null;
        $isMarketing = Str::snake((string) ($options['template_category'] ?? '')) === 'marketing';
        $token = ($user->exists && ($emailType || $isMarketing || ($options['include_unsubscribe'] ?? false)))
            ? $this->tokens->issue($user, $emailType ?: 'marketing')
            : null;

        return [
            'user_name' => $user->name ?: Str::before((string) $user->email, '@'),
            'user_email' => $user->email,
            'platform_name' => config('app.name', 'BDGigs'),
            'dashboard_url' => url('/dashboard'),
            'support_url' => url('/dashboard/settings'),
            'preferences_url' => $token ? url('/email/preferences/'.$token) : url('/dashboard/settings/notifications'),
            'unsubscribe_url' => $token ? url('/email/unsubscribe/'.$token) : '',
            'notification_title' => '',
            'notification_detail' => '',
            ...$data,
            'action_url' => $this->absoluteUrl($actionUrl),
            'order_url' => $this->absoluteUrl($data['order_url'] ?? ''),
            'conversation_url' => $this->absoluteUrl($data['conversation_url'] ?? ''),
        ];
    }

    private function failWithoutTemplate(string $templateKey, User $user, array $data): bool
    {
        $this->logEmail([
            'user_id' => $user->id,
            'email_template_key' => $templateKey,
            'recipient_email' => $user->email,
            'subject' => 'Missing email template',
            'status' => 'failed',
            'error_message' => 'No active email template or default exists for '.$templateKey,
            'payload' => $data,
        ]);

        return false;
    }

    private function markLog(?EmailLog $log, string $status, ?string $error = null): void
    {
        if (! $log) {
            return;
        }

        $log->forceFill([
            'status' => $status,
            'error_message' => $error,
            'sent_at' => $status === 'sent' ? now() : $log->sent_at,
        ])->save();
    }

    private function usesLogTransport(): bool
    {
        return in_array(config('mail.default'), ['log', 'array'], true);
    }

    private function mailerConfig(): array
    {
        return config('mail.mailers.phpmailer') ?: config('mail.mailers.smtp', []);
    }

    private function legacyPreferenceKey(string $category): ?string
    {
        return match ($category) {
            'orders', 'time_extensions', 'disputes' => 'orderUpdates',
            'custom_offers', 'messages' => 'inboxMessages',
            'reviews' => 'ratingReminders',
            'payments' => 'payouts',
            'account', 'security', 'identity' => 'accountUpdates',
            'gigs' => 'gigUpdates',
            'marketing' => 'buyerBriefs',
            default => null,
        };
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html))));
    }

    private function preheader(string $text): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $text) ?? ''), 140, '');
    }

    private function absoluteUrl(mixed $url): string
    {
        if (blank($url)) {
            return '';
        }

        return Str::startsWith((string) $url, ['http://', 'https://'])
            ? (string) $url
            : url((string) $url);
    }
}
