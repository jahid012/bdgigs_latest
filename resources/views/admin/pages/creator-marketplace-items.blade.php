@extends('admin.layouts.panel')

@section('title', 'Creator Marketplace')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-detail-layout">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Slider items</h2>
                    <p>Active cards are loaded by the homepage creator marketplace slider.</p>
                </div>
            </div>
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.creator-marketplace') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search cards">
                </label>
                <button type="submit">Search cards</button>
                @if ($searchQuery !== '')
                    <a href="{{ route('admin.creator-marketplace') }}">Clear</a>
                @endif
            </form>
            <div class="admin-card-list">
                @forelse ($items as $item)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $item->title }}</strong>
                            <p>{{ $item->link_url ?: 'No custom link' }} - Sort {{ $item->sort_order }}</p>
                        </div>
                        <div>
                            <span class="admin-status-badge {{ $item->active ? 'status-completed' : 'status-cancelled' }}">{{ $item->active ? 'Active' : 'Inactive' }}</span>
                            @if ($item->image)
                                <a class="admin-panel-link" href="{{ $item->image }}" target="_blank" rel="noreferrer">Image</a>
                            @endif
                        </div>
                    </article>
                    <form class="admin-detail-form admin-category-edit-form" method="POST" action="{{ route('admin.creator-marketplace.update', $item) }}">
                        @csrf
                        @method('PATCH')
                        @include('admin.pages.partials.creator-marketplace-fields', ['item' => $item])
                        <div>
                            <button type="submit">Save {{ $item->title }}</button>
                            <button class="is-danger" type="submit" form="delete-creator-item-{{ $item->id }}">Delete</button>
                        </div>
                    </form>
                    <form id="delete-creator-item-{{ $item->id }}" method="POST" action="{{ route('admin.creator-marketplace.destroy', $item) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p class="admin-empty-note">No creator marketplace cards have been created yet.</p>
                @endforelse
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Add slider item</h2>
                    <p>Use public asset paths or external URLs for images.</p>
                </div>
            </div>
            <form class="admin-detail-form" method="POST" action="{{ route('admin.creator-marketplace.store') }}">
                @csrf
                @include('admin.pages.partials.creator-marketplace-fields', ['item' => null])
                <button type="submit">Create card</button>
            </form>
        </aside>
    </section>
@endsection
