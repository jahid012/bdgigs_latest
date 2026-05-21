@extends('admin.layouts.panel')

@section('title', 'Gigs')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Gig moderation</h2>
                    <p>Approve, request edits, or watch published service quality.</p>
                </div>
            </div>
            <form class="admin-user-search-form" method="GET" action="{{ route('admin.gigs') }}">
                <input type="hidden" name="status" value="{{ $currentFilter }}">
                <label>
                    <span>Gig search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Search title, seller, category, or status">
                </label>
                <button type="submit">Search gigs</button>
                @if ($searchQuery !== '' || $currentFilter !== 'all')
                    <a href="{{ route('admin.gigs') }}">Clear</a>
                @endif
            </form>
            <div class="admin-filter-row">
                @foreach ($filters as $filter)
                    <a
                        class="{{ $currentFilter === $filter['value'] ? 'is-active' : '' }}"
                        href="{{ request()->fullUrlWithQuery(['status' => $filter['value'], 'page' => 1]) }}"
                    >
                        {{ $filter['label'] }} <span>{{ number_format($filter['count']) }}</span>
                    </a>
                @endforeach
            </div>
            <div class="admin-card-list">
                @forelse ($gigs as $gig)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $gig['title'] }}</strong>
                            <p>{{ $gig['seller'] }} - {{ $gig['category'] }} - Updated {{ $gig['updated'] }}</p>
                        </div>
                        <div>
                            <span class="admin-status-badge {{ $gig['status_class'] }}">{{ $gig['status'] }}</span>
                            <b>{{ $gig['price'] }}</b>
                            <div class="admin-row-actions">
                                @can('gigs.publish')
                                    <form method="POST" action="{{ route('admin.gigs.status', $gig['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="publish">
                                        <button type="submit">Publish</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.gigs.status', $gig['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="pause">
                                        <button type="submit">Pause</button>
                                    </form>
                                @endcan
                                @can('gigs.review')
                                    <form method="POST" action="{{ route('admin.gigs.status', $gig['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="request_edits">
                                        <button type="submit">Request edits</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.gigs.status', $gig['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit">Reject</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-note">No gigs matched your filters.</p>
                @endforelse
            </div>
            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Gig moderation pagination'])
        </article>

        <aside class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Review checklist</h2>
                    <p>Keep quality consistent before publishing.</p>
                </div>
            </div>
            <ol class="admin-activity-list">
                <li>Title explains the exact deliverable.</li>
                <li>Images are readable and not misleading.</li>
                <li>Pricing matches scope and package details.</li>
                <li>No contact details outside the platform.</li>
            </ol>
        </aside>
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Category health</h2>
                    <p>Inventory quality by top marketplace category.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @forelse ($categoryHealth as $category)
                    <span style="--value: {{ $category['value'] }}%"><b>{{ $category['label'] }}</b><em>{{ $category['value'] }}%</em></span>
                @empty
                    <span style="--value: 0%"><b>No categories yet</b><em>0%</em></span>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Common rejection reasons</h2>
                    <p>Use these signals to improve seller guidance.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @foreach ($rejectionReasons as $reason)
                    <article class="admin-mini-card"><div><strong>{{ $reason['label'] }}</strong><p>{{ $reason['meta'] }}</p></div><b>{{ $reason['tone'] }}</b></article>
                @endforeach
            </div>
        </article>
    </section>
@endsection
