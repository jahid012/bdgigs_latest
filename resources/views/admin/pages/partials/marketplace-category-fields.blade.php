@php
    $editingId = $category?->id;
@endphp

<label>
    <span>Parent category</span>
    <select name="parent_id">
        <option value="">Top level category</option>
        @foreach ($parentOptions as $parent)
            @if ($editingId !== $parent->id)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category?->parent_id) == $parent->id)>{{ $parent->name }}</option>
            @endif
        @endforeach
    </select>
</label>
<label>
    <span>Name</span>
    <input name="name" type="text" value="{{ old('name', $category?->name) }}" required maxlength="120" placeholder="Programming & Tech">
</label>
<label>
    <span>Slug</span>
    <input name="slug" type="text" value="{{ old('slug', $category?->slug) }}" maxlength="140" placeholder="programming-tech">
</label>
<label>
    <span>Description</span>
    <textarea name="description" rows="2" maxlength="500" placeholder="Short category description">{{ old('description', $category?->description) }}</textarea>
</label>
<label>
    <span>Icon</span>
    <input name="icon" type="text" value="{{ old('icon', $category?->icon) }}" maxlength="80" placeholder="code">
</label>
<label>
    <span>Image path</span>
    <input name="image" type="text" value="{{ old('image', $category?->image) }}" maxlength="255" placeholder="/assets/img/gig_images/1.png">
</label>
<label>
    <span>Link URL</span>
    <input name="link_url" type="text" value="{{ old('link_url', $category?->link_url) }}" maxlength="255" placeholder="/categories/programming-tech/website-development">
</label>
<label>
    <span>Sort order</span>
    <input name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $category?->sort_order ?? 0) }}">
</label>
<label class="admin-setting-row-toggle">
    <span class="admin-setting-copy">
        <strong>Active</strong>
        <small>Inactive categories stay hidden from the frontend.</small>
    </span>
    <span class="admin-setting-switch">
        <input name="active" value="1" type="checkbox" @checked(old('active', $category?->active ?? true))>
        <i></i>
    </span>
</label>
<label class="admin-setting-row-toggle">
    <span class="admin-setting-copy">
        <strong>Show in mega menu</strong>
        <small>Controls public header visibility.</small>
    </span>
    <span class="admin-setting-switch">
        <input name="show_in_mega_menu" value="1" type="checkbox" @checked(old('show_in_mega_menu', $category?->show_in_mega_menu ?? true))>
        <i></i>
    </span>
</label>
