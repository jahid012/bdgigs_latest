<label>
    <span>Title</span>
    <input name="title" type="text" value="{{ old('title', $item?->title) }}" required maxlength="140" placeholder="Website Development">
</label>
<label>
    <span>Description</span>
    <textarea name="description" rows="2" maxlength="700" placeholder="Optional supporting copy">{{ old('description', $item?->description) }}</textarea>
</label>
<label>
    <span>Image path</span>
    <input name="image" type="text" value="{{ old('image', $item?->image) }}" maxlength="255" placeholder="/assets/img/gig_images/3.png">
</label>
<label>
    <span>Icon</span>
    <input name="icon" type="text" value="{{ old('icon', $item?->icon) }}" maxlength="80" placeholder="code">
</label>
<label>
    <span>Link URL</span>
    <input name="link_url" type="text" value="{{ old('link_url', $item?->link_url) }}" maxlength="255" placeholder="/search/gigs?query=website%20development">
</label>
<label>
    <span>Card color</span>
    <input name="color" type="text" value="{{ old('color', ($item?->metadata ?? [])['color'] ?? '') }}" maxlength="40" placeholder="#c9f8e8">
</label>
<label>
    <span>Sort order</span>
    <input name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $item?->sort_order ?? 0) }}">
</label>
<label class="admin-setting-row-toggle">
    <span class="admin-setting-copy">
        <strong>Active</strong>
        <small>Inactive cards stay hidden from the homepage slider.</small>
    </span>
    <span class="admin-setting-switch">
        <input name="active" value="1" type="checkbox" @checked(old('active', $item?->active ?? true))>
        <i></i>
    </span>
</label>
