<section class="admin-stat-grid" aria-label="Page stats">
    @foreach ($stats as $stat)
        <article class="admin-stat-card">
            <span>{{ $stat['label'] }}</span>
            <strong>{{ $stat['value'] }}</strong>
            <p>{{ $stat['meta'] }}</p>
        </article>
    @endforeach
</section>
