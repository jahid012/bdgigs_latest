@php
    $chartId = 'admin-chart-' . md5($chart['title'] ?? uniqid('chart', true));
    $datasets = $chart['datasets'] ?? [];
@endphp

<article class="admin-panel admin-line-chart-card">
    <div class="admin-panel-head admin-chart-panel-head">
        <div>
            <h2>{{ $chart['title'] }}</h2>
            <p>{{ $chart['description'] }}</p>
        </div>

        <form class="admin-chart-controls" method="GET">
            @foreach (($chart['controls'] ?? []) as $control)
                <label>
                    <span>{{ $control['label'] }}</span>
                    @if (($control['type'] ?? 'date') === 'select')
                        <select name="{{ $control['name'] }}">
                            @foreach (($control['options'] ?? []) as $option)
                                <option value="{{ $option }}" @selected(($control['value'] ?? '') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="{{ $control['type'] ?? 'date' }}"
                            name="{{ $control['name'] }}"
                            value="{{ $control['value'] ?? '' }}"
                        >
                    @endif
                </label>
            @endforeach
            <button type="submit">Update</button>
        </form>
    </div>

    <div class="admin-line-chart-wrap">
        <canvas
            id="{{ $chartId }}"
            class="admin-line-chart"
            data-admin-line-chart
            data-labels='@json($chart['labels'] ?? [])'
            data-datasets='@json($datasets)'
            data-max="{{ $chart['max'] ?? '' }}"
        ></canvas>
    </div>

    <div class="admin-chart-footer">
        <div class="admin-chart-legend">
            @foreach ($datasets as $dataset)
                <span><i style="--legend-color: {{ $dataset['color'] }}"></i>{{ $dataset['label'] }}</span>
            @endforeach
        </div>

        @isset($chart['summary'])
            <div class="admin-chart-summary">
                @foreach ($chart['summary'] as $item)
                    <span><b>{{ $item['value'] }}</b>{{ $item['label'] }}</span>
                @endforeach
            </div>
        @endisset
    </div>
</article>
