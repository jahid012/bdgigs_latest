@extends('admin.layouts.panel')

@section('title', 'Seller Applications')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-page-grid">
        <article class="admin-panel admin-table-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller review queue</h2>
                    <p>Review pending sellers and keep approval history auditable.</p>
                </div>
            </div>

            <form class="admin-user-filter-form" method="GET" action="{{ route('admin.seller-applications') }}">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $searchQuery }}" placeholder="Name, username, or email">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        @foreach ($filters as $filter)
                            <option value="{{ $filter['value'] }}" @selected($currentStatus === $filter['value'])>{{ $filter['label'] }} ({{ $filter['count'] }})</option>
                        @endforeach
                    </select>
                </label>
                <div>
                    <button type="submit">Apply filters</button>
                    <a href="{{ route('admin.seller-applications') }}">Clear</a>
                </div>
            </form>

            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr><th>Seller</th><th>Status</th><th>Reason</th><th>Reviewed</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($sellers as $seller)
                            <tr>
                                <td>
                                    <strong>{{ $seller->name ?: $seller->email }}</strong>
                                    <small>{{ '@'.$seller->username }} - {{ $seller->email }}</small>
                                </td>
                                <td><span class="admin-status-badge {{ $seller->seller_status === 'approved' ? 'is-good' : ($seller->seller_status === 'rejected' ? 'is-danger' : 'is-warn') }}">{{ str($seller->seller_status)->replace('_', ' ')->title() }}</span></td>
                                <td>{{ str($seller->seller_status_reason ?: 'No reason recorded.')->limit(80) }}</td>
                                <td>{{ $seller->seller_status_reviewed_at?->format('M j, Y') ?? 'Not reviewed' }}</td>
                                <td><a class="admin-panel-link" href="{{ route('admin.seller-applications.show', $seller) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No seller applications matched this queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['pagination' => $pagination, 'label' => 'Seller application pagination'])
        </article>
    </section>
@endsection
