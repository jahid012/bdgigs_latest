@extends('admin.layouts.panel')

@section('title', 'Reports')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    <section class="admin-reports-overview-grid">
        @include('admin.partials.line-chart', ['chart' => $marketplaceGrowth])

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Category segments</h2>
                    <p>Top sales segments this month.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @foreach ($segments as $segment)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $segment['name'] }}</strong>
                            <p>{{ $segment['growth'] }} growth</p>
                        </div>
                        <b>{{ $segment['sales'] }}</b>
                    </article>
                @endforeach
            </div>
        </article>
    </section>

    <section class="admin-reports-chart-grid">
        @include('admin.partials.line-chart', ['chart' => $visitorAnalytics])
        @include('admin.partials.line-chart', ['chart' => $profileActivityGrowth])
    </section>

    <section class="admin-workflow-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Buyer behavior</h2>
                    <p>Signals that help tune acquisition and retention.</p>
                </div>
            </div>
            <div class="admin-quality-bars">
                @foreach ($buyerBehavior as $bar)
                    <span style="--value: {{ $bar['value'] }}%"><b>{{ $bar['label'] }}</b><em>{{ $bar['value'] }}%</em></span>
                @endforeach
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Scheduled reports</h2>
                    <p>Reusable reporting moments for admins.</p>
                </div>
            </div>
            <div class="admin-workflow-steps">
                <span><b>M</b><strong>Marketplace weekly</strong><small>Every Monday</small></span>
                <span><b>F</b><strong>Finance digest</strong><small>End of month</small></span>
                <span><b>T</b><strong>Trust and safety</strong><small>Every Friday</small></span>
            </div>
        </article>
    </section>
@endsection

@push('scripts')
    @include('admin.partials.line-chart-scripts')
@endpush
