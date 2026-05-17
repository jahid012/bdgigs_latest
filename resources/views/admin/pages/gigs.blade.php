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
                <button type="button">Bulk review</button>
            </div>
            <div class="admin-card-list">
                @foreach ($gigs as $gig)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $gig['title'] }}</strong>
                            <p>{{ $gig['seller'] }} - {{ $gig['category'] }}</p>
                        </div>
                        <div>
                            <span>{{ $gig['status'] }}</span>
                            <b>{{ $gig['price'] }}</b>
                        </div>
                    </article>
                @endforeach
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
                <span style="--value: 91%"><b>Programming & Tech</b><em>91%</em></span>
                <span style="--value: 84%"><b>Digital Marketing</b><em>84%</em></span>
                <span style="--value: 78%"><b>Graphics & Design</b><em>78%</em></span>
                <span style="--value: 70%"><b>AI Services</b><em>70%</em></span>
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
                <article class="admin-mini-card"><div><strong>Unclear package scope</strong><p>32% of rejected gigs</p></div><b>High</b></article>
                <article class="admin-mini-card"><div><strong>Low quality gallery image</strong><p>24% of rejected gigs</p></div><b>Medium</b></article>
                <article class="admin-mini-card"><div><strong>External contact details</strong><p>8% of rejected gigs</p></div><b>Critical</b></article>
            </div>
        </article>
    </section>
@endsection
