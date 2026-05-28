<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailTemplate;
use App\Support\EmailTemplateDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('emails.manage') ?? false;
    }

    public function rules(): array
    {
        $template = $this->route('emailTemplate');

        return [
            'key' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique(EmailTemplate::class, 'key')->ignore($template?->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(array_keys(EmailTemplateDefaults::categories()))],
            'is_active' => ['nullable', 'boolean'],
            'available_variables' => ['nullable', 'string'],
        ];
    }

    public function payload(): array
    {
        $validated = $this->validated();

        return [
            ...$validated,
            'is_active' => $this->boolean('is_active'),
            'available_variables' => $this->variables($validated['available_variables'] ?? ''),
        ];
    }

    private function variables(string $variables): array
    {
        if (trim($variables) === '') {
            return EmailTemplateDefaults::VARIABLES;
        }

        return collect(preg_split('/[\s,]+/', $variables) ?: [])
            ->map(fn (string $variable) => trim($variable))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
