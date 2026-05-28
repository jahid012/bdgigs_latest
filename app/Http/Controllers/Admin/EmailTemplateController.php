<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEmailTemplateRequest;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use App\Support\EmailTemplateDefaults;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', ''));

        $templates = EmailTemplate::query()
            ->when($searchQuery !== '', function ($query) use ($searchQuery) {
                $query->where(function ($query) use ($searchQuery) {
                    $query->where('key', 'like', "%{$searchQuery}%")
                        ->orWhere('name', 'like', "%{$searchQuery}%")
                        ->orWhere('subject', 'like', "%{$searchQuery}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();

        return view('admin.pages.email-templates', [
            'pageEyebrow' => 'Email system',
            'pageTitle' => 'Email Templates',
            'pageDescription' => 'Manage transactional, marketplace, and marketing email templates sent through PHPMailer.',
            'searchPlaceholder' => 'Search email templates',
            'templates' => $templates,
            'categories' => EmailTemplateDefaults::categories(),
            'defaultVariables' => EmailTemplateDefaults::VARIABLES,
            'searchQuery' => $searchQuery,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'stats' => [
                ['label' => 'Templates', 'value' => number_format(EmailTemplate::count()), 'meta' => 'Managed templates'],
                ['label' => 'Active', 'value' => number_format(EmailTemplate::where('is_active', true)->count()), 'meta' => 'Enabled now'],
                ['label' => 'Failed emails', 'value' => number_format(EmailLog::where('status', 'failed')->count()), 'meta' => 'Needs review'],
                ['label' => 'Sent today', 'value' => number_format(EmailLog::whereDate('sent_at', today())->where('status', 'sent')->count()), 'meta' => 'PHPMailer/log transport'],
            ],
        ]);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $logs = EmailLog::with('user')
            ->where('email_template_key', $emailTemplate->key)
            ->latest()
            ->limit(40)
            ->get();

        return view('admin.pages.email-template-details', [
            'pageEyebrow' => 'Email system',
            'pageTitle' => $emailTemplate->name,
            'pageDescription' => 'Edit template copy, preview the rendered email, send test messages, and inspect delivery logs.',
            'searchPlaceholder' => 'Search email templates',
            'template' => $emailTemplate,
            'logs' => $logs,
            'categories' => EmailTemplateDefaults::categories(),
            'defaultVariables' => EmailTemplateDefaults::VARIABLES,
            'stats' => [
                ['label' => 'Status', 'value' => $emailTemplate->is_active ? 'Active' : 'Inactive', 'meta' => 'Template state'],
                ['label' => 'Category', 'value' => EmailTemplateDefaults::categories()[$emailTemplate->category] ?? $emailTemplate->category, 'meta' => 'Preference group'],
                ['label' => 'Sent', 'value' => number_format(EmailLog::where('email_template_key', $emailTemplate->key)->where('status', 'sent')->count()), 'meta' => 'All time'],
                ['label' => 'Failed', 'value' => number_format(EmailLog::where('email_template_key', $emailTemplate->key)->where('status', 'failed')->count()), 'meta' => 'All time'],
            ],
        ]);
    }

    public function store(StoreEmailTemplateRequest $request)
    {
        EmailTemplate::create($request->payload());

        return back()->withNotify('success', 'Email template created.', 'Template saved');
    }

    public function update(StoreEmailTemplateRequest $request, EmailTemplate $emailTemplate)
    {
        $emailTemplate->update($request->payload());

        return back()->withNotify('success', 'Email template updated.', 'Template saved');
    }

    public function reset(EmailTemplate $emailTemplate)
    {
        $default = EmailTemplateDefaults::get($emailTemplate->key);

        abort_unless($default, 404, 'No default template exists for this key.');

        $emailTemplate->update(collect($default)->except('key')->all());

        return back()->withNotify('success', 'Template reset to the default copy.', 'Template reset');
    }

    public function preview(EmailTemplate $emailTemplate, EmailService $emails)
    {
        $preview = $emails->preview($emailTemplate->key, $this->demoPayload());

        return response($preview['html']);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate, EmailService $emails)
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $admin = $request->user()->replicate();
        $admin->id = $request->user()->id;
        $admin->name = $request->user()->name ?: 'Admin';
        $admin->email = $payload['email'];
        $admin->exists = true;

        $sent = $emails->sendTemplateEmail($emailTemplate->key, $admin, [
            ...$this->demoPayload(),
            'user_name' => $admin->name,
        ], [
            'force' => true,
        ]);

        return back()->withNotify(
            $sent ? 'success' : 'error',
            $sent ? 'Test email sent or logged successfully.' : 'Test email could not be sent. Check email logs.',
            $sent ? 'Test sent' : 'Test failed',
        );
    }

    private function demoPayload(): array
    {
        return [
            'buyer_name' => 'Cloudpeak Labs',
            'seller_name' => 'Wiznic Solution',
            'sender_name' => 'Cloudpeak Labs',
            'order_id' => 'DU-B-0025-02',
            'order_title' => 'Complete SaaS Dashboard UI Design',
            'order_amount' => '$140',
            'gig_title' => 'Web Application Development',
            'custom_offer_title' => 'Custom Laravel Marketplace Build',
            'custom_offer_price' => '$400',
            'delivery_time' => '5 days',
            'deadline' => now()->addDays(5)->format('M j, Y'),
            'review_deadline' => now()->addDays(15)->format('M j, Y'),
            'conversation_subject' => 'SaaS dashboard project',
            'notification_detail' => 'This is sample data used to preview the email template.',
            'action_url' => url('/dashboard'),
        ];
    }
}
