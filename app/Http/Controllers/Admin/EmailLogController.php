<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailLogController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $template = trim((string) $request->query('template', ''));
        $user = trim((string) $request->query('user', ''));
        $dateFrom = trim((string) $request->query('from', ''));
        $dateTo = trim((string) $request->query('to', ''));

        $logs = EmailLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('recipient_email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('email_template_key', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($template !== '', fn ($query) => $query->where('email_template_key', $template))
            ->when($user !== '', function ($query) use ($user) {
                $query->whereHas('user', function ($query) use ($user) {
                    $query
                        ->where('name', 'like', "%{$user}%")
                        ->orWhere('email', 'like', "%{$user}%");
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return $this->panelView('admin.pages.email-logs', [
            'pageEyebrow' => 'Email system',
            'pageTitle' => 'Email Logs',
            'pageDescription' => 'Inspect PHPMailer delivery attempts, payloads, rendered previews, and retry failed transactional emails.',
            'searchPlaceholder' => 'Search email logs',
            'logs' => $logs,
            'templates' => EmailTemplate::orderBy('name')->get(['key', 'name']),
            'filters' => compact('search', 'status', 'template', 'user', 'dateFrom', 'dateTo'),
            'stats' => [
                ['label' => 'Total logs', 'value' => number_format(EmailLog::count()), 'meta' => 'All attempts'],
                ['label' => 'Sent', 'value' => number_format(EmailLog::where('status', 'sent')->count()), 'meta' => 'Delivered/logged'],
                ['label' => 'Failed', 'value' => number_format(EmailLog::where('status', 'failed')->count()), 'meta' => 'Needs retry'],
                ['label' => 'Pending', 'value' => number_format(EmailLog::where('status', 'pending')->count()), 'meta' => 'Queued or in-flight'],
            ],
        ]);
    }

    public function show(EmailLog $emailLog, EmailService $emails)
    {
        $emailLog->load('user');
        $preview = $emailLog->email_template_key
            ? $emails->preview($emailLog->email_template_key, $emailLog->payload ?: [])
            : ['subject' => $emailLog->subject, 'html' => '', 'text' => ''];

        return $this->panelView('admin.pages.email-log-details', [
            'pageEyebrow' => 'Email system',
            'pageTitle' => 'Email Log #'.$emailLog->id,
            'pageDescription' => 'Review the attempted email payload, rendered preview, status, and delivery error.',
            'searchPlaceholder' => 'Search email logs',
            'emailLog' => $emailLog,
            'template' => $emailLog->email_template_key
                ? EmailTemplate::where('key', $emailLog->email_template_key)->first()
                : null,
            'preview' => $preview,
            'stats' => [
                ['label' => 'Status', 'value' => str($emailLog->status)->title()->toString(), 'meta' => 'Delivery state'],
                ['label' => 'Template', 'value' => $emailLog->email_template_key ?: 'Raw email', 'meta' => 'Template key'],
                ['label' => 'Recipient', 'value' => $emailLog->recipient_email, 'meta' => 'To address'],
                ['label' => 'Sent at', 'value' => $emailLog->sent_at?->format('M j') ?? 'Not sent', 'meta' => $emailLog->created_at?->format('M j, Y g:i A')],
            ],
        ]);
    }

    public function retry(EmailLog $emailLog, EmailService $emails)
    {
        abort_unless($emailLog->status === 'failed', 422, 'Only failed emails can be retried.');
        abort_unless($emailLog->email_template_key, 422, 'Only template emails can be retried.');

        $user = $emailLog->user ?: User::where('email', $emailLog->recipient_email)->first();

        if (! $user) {
            $user = new User([
                'name' => $emailLog->recipient_email,
                'email' => $emailLog->recipient_email,
            ]);
            $user->exists = true;
        }

        $sent = $emails->sendTemplateEmail($emailLog->email_template_key, $user, [
            ...($emailLog->payload ?: []),
            'retried_from_log_id' => $emailLog->id,
        ], [
            'force' => true,
        ]);

        return back()->withNotify(
            $sent ? 'success' : 'error',
            $sent ? 'Email retry sent or logged successfully.' : 'Retry failed. Review the newest email log.',
            $sent ? 'Retry sent' : 'Retry failed',
        );
    }
}
