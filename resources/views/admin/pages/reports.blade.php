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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Chart) {
                return;
            }

            document.querySelectorAll('[data-admin-line-chart]').forEach((canvas) => {
                const labels = JSON.parse(canvas.dataset.labels || '[]');
                const rawDatasets = JSON.parse(canvas.dataset.datasets || '[]');
                const suggestedMax = Number(canvas.dataset.max || 0);

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: rawDatasets.map((dataset) => ({
                            label: dataset.label,
                            data: dataset.values,
                            borderColor: dataset.color,
                            backgroundColor: dataset.fill || dataset.color,
                            fill: Boolean(dataset.fill),
                            tension: 0.38,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: dataset.color,
                            pointBorderWidth: 2,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: '#222326',
                                borderColor: 'rgba(255, 255, 255, 0.12)',
                                borderWidth: 1,
                                displayColors: true,
                                padding: 10,
                                titleFont: {
                                    size: 12,
                                    weight: '800',
                                },
                                bodyFont: {
                                    size: 12,
                                    weight: '700',
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                },
                                border: {
                                    display: false,
                                },
                                ticks: {
                                    color: '#62646a',
                                    font: {
                                        size: 11,
                                        weight: '700',
                                    },
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: 8,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                suggestedMax: suggestedMax || undefined,
                                grid: {
                                    color: '#eef0f2',
                                },
                                border: {
                                    display: false,
                                },
                                ticks: {
                                    color: '#62646a',
                                    font: {
                                        size: 11,
                                        weight: '700',
                                    },
                                },
                            },
                        },
                    },
                });
            });
        });
    </script>
@endpush
