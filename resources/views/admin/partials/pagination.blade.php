@php
    $currentPage = (int) ($pagination['currentPage'] ?? 1);
    $lastPage = (int) ($pagination['lastPage'] ?? 1);
    $from = (int) ($pagination['from'] ?? 0);
    $to = (int) ($pagination['to'] ?? 0);
    $total = (int) ($pagination['total'] ?? 0);
    $pages = $pagination['pages'] ?? range(1, min($lastPage, 5));
    $lastRenderedPage = $pages[count($pages) - 1] ?? 1;
@endphp

@if ($lastPage > 1)
    <nav class="admin-pagination" aria-label="{{ $label ?? 'Pagination' }}">
        <p>
            Showing <strong>{{ number_format($from) }}-{{ number_format($to) }}</strong>
            of <strong>{{ number_format($total) }}</strong>
        </p>

        <div>
            @if ($currentPage > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">Previous</a>
            @else
                <button type="button" disabled>Previous</button>
            @endif

            @foreach ($pages as $page)
                <a
                    class="{{ $page === $currentPage ? 'is-active' : '' }}"
                    href="{{ request()->fullUrlWithQuery(['page' => $page]) }}"
                    @if ($page === $currentPage) aria-current="page" @endif
                >
                    {{ $page }}
                </a>
            @endforeach

            @if ($lastRenderedPage < $lastPage)
                <span aria-hidden="true">...</span>
                <a href="{{ request()->fullUrlWithQuery(['page' => $lastPage]) }}">{{ number_format($lastPage) }}</a>
            @endif

            @if ($currentPage < $lastPage)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">Next</a>
            @else
                <button type="button" disabled>Next</button>
            @endif
        </div>
    </nav>
@endif
