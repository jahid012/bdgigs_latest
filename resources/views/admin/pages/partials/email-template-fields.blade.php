@php
    $variables = old('available_variables', implode(', ', $template?->available_variables ?: $defaultVariables));
@endphp

<label>
    <span>Template key</span>
    <input type="text" name="key" value="{{ old('key', $template?->key) }}" placeholder="new_order_created" required>
</label>
<label>
    <span>Name</span>
    <input type="text" name="name" value="{{ old('name', $template?->name) }}" placeholder="New order created" required>
</label>
<label>
    <span>Category</span>
    <select name="category" required>
        @foreach ($categories as $key => $label)
            <option value="{{ $key }}" @selected(old('category', $template?->category) === $key)>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>
    <span>Subject</span>
    <input type="text" name="subject" value="{{ old('subject', $template?->subject) }}" required>
</label>
<label>
    <span>HTML body</span>
    <textarea name="html_body" rows="8" required>{{ old('html_body', $template?->html_body) }}</textarea>
</label>
<label>
    <span>Plain text body</span>
    <textarea name="text_body" rows="5">{{ old('text_body', $template?->text_body) }}</textarea>
</label>
<label>
    <span>Available variables</span>
    <textarea name="available_variables" rows="4">{{ $variables }}</textarea>
</label>
<label class="admin-check-row">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template?->is_active ?? true))>
    <span>Template is active</span>
</label>
