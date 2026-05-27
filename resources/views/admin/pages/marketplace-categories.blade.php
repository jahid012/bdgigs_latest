@extends('admin.layouts.panel')

@section('title', 'Marketplace Categories')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Category tree</h2>
                    <p>Only active categories marked for the mega menu appear in the marketplace header.</p>
                </div>
            </div>
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.marketplace-categories') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search categories or slugs">
                </label>
                <button type="submit">Search categories</button>
                @if ($searchQuery !== '')
                    <a href="{{ route('admin.marketplace-categories') }}">Clear</a>
                @endif
            </form>
            <div class="admin-card-list">
                @forelse ($categories as $category)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $category->name }}</strong>
                            <p>{{ $category->slug }} - {{ $category->children->count() }} subcategories - Sort {{ $category->sort_order }}</p>
                        </div>
                        <div>
                            <span class="admin-status-badge {{ $category->active ? 'status-completed' : 'status-cancelled' }}">{{ $category->active ? 'Active' : 'Inactive' }}</span>
                            <span class="admin-status-badge {{ $category->show_in_mega_menu ? 'status-progress' : 'status-delivered' }}">{{ $category->show_in_mega_menu ? 'Mega menu' : 'Hidden' }}</span>
                        </div>
                    </article>
                    <form class="admin-detail-form admin-category-edit-form" method="POST" action="{{ route('admin.marketplace-categories.update', $category) }}">
                        @csrf
                        @method('PATCH')
                        @include('admin.pages.partials.marketplace-category-fields', ['category' => $category, 'parentOptions' => $parentOptions])
                        <div>
                            <button type="submit">Save {{ $category->name }}</button>
                        </div>
                    </form>
                    <div class="admin-card-list compact">
                        @forelse ($category->children as $child)
                            <article class="admin-mini-card">
                                <div>
                                    <strong>{{ $child->name }}</strong>
                                    <p>{{ $child->slug }} - Sort {{ $child->sort_order }}</p>
                                </div>
                                <div>
                                    <span class="admin-status-badge {{ $child->active ? 'status-completed' : 'status-cancelled' }}">{{ $child->active ? 'Active' : 'Inactive' }}</span>
                                    <span class="admin-status-badge {{ $child->show_in_mega_menu ? 'status-progress' : 'status-delivered' }}">{{ $child->show_in_mega_menu ? 'Mega menu' : 'Hidden' }}</span>
                                </div>
                            </article>
                            <form class="admin-detail-form admin-category-edit-form" method="POST" action="{{ route('admin.marketplace-categories.update', $child) }}">
                                @csrf
                                @method('PATCH')
                                @include('admin.pages.partials.marketplace-category-fields', ['category' => $child, 'parentOptions' => $parentOptions])
                                <div>
                                    <button type="submit">Save {{ $child->name }}</button>
                                    <button class="is-danger" type="submit" form="delete-category-{{ $child->id }}">Delete</button>
                                </div>
                            </form>
                            <form id="delete-category-{{ $child->id }}" method="POST" action="{{ route('admin.marketplace-categories.destroy', $child) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        @empty
                            <p class="admin-empty-note">No subcategories under {{ $category->name }} yet.</p>
                        @endforelse
                    </div>
                    <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.marketplace-categories.destroy', $category) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p class="admin-empty-note">No categories have been created yet.</p>
                @endforelse
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Add category</h2>
                    <p>Create a top-level category or attach it to an existing parent.</p>
                </div>
            </div>
            <form class="admin-detail-form" method="POST" action="{{ route('admin.marketplace-categories.store') }}">
                @csrf
                @include('admin.pages.partials.marketplace-category-fields', ['category' => null, 'parentOptions' => $parentOptions])
                <button type="submit">Create category</button>
            </form>
        </aside>
    </section>
@endsection
