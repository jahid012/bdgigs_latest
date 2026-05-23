@extends('admin.layouts.panel')

@section('title', 'Gig Details')

@section('panel')
    @include('admin.partials.stats', ['stats' => $stats])

    @php
        $packages = collect($gig->packages ?? []);
        $requirements = collect($gig->requirements ?? []);
        $extras = collect($gig->extras ?? []);
        $gallery = collect($gig->gallery_images ?? [])->filter();
        $sellerDetails = collect($gig->seller_details ?? [])->filter();
        $serviceOptions = collect($gig->service_options ?? [])->filter();
        $metadata = $gig->metadata ?? [];
    @endphp

    <section class="admin-detail-layout">
        <article class="admin-panel admin-detail-summary">
            <div class="admin-detail-head">
                <div class="admin-detail-media">
                    @if ($gig->image)
                        <img src="{{ $gig->image }}" alt="{{ $gig->title }}">
                    @else
                        <span>No image</span>
                    @endif
                </div>
                <div>
                    <div class="admin-detail-badges">
                        <span class="admin-status-badge {{ $gig->trashed() ? 'status-cancelled' : $gig->status_class }}">
                            {{ $gig->trashed() ? 'Deleted' : $gig->status }}
                        </span>
                        @if ($gig->featured)
                            <span class="admin-status-badge status-completed">Featured</span>
                        @endif
                    </div>
                    <h2>{{ $gig->title }}</h2>
                    <p>
                        {{ $gig->seller?->name ?: $gig->seller_name }} -
                        {{ $gig->category_label ?: 'Uncategorized' }}
                    </p>
                </div>
            </div>

            <dl class="admin-user-detail-list admin-detail-list">
                <div><dt>Slug</dt><dd>{{ $gig->slug }}</dd></div>
                <div><dt>Seller level</dt><dd>{{ $gig->seller_level ?: 'Not recorded' }}</dd></div>
                <div><dt>Delivery</dt><dd>{{ $gig->delivery_days }} days</dd></div>
                <div><dt>Options</dt><dd>{{ $serviceOptions->isEmpty() ? 'None' : $serviceOptions->join(', ') }}</dd></div>
                <div><dt>Created</dt><dd>{{ $gig->created_at?->format('M j, Y') ?? 'Unknown' }}</dd></div>
                <div><dt>Updated</dt><dd>{{ $gig->updated_at?->diffForHumans() ?? 'Unknown' }}</dd></div>
            </dl>

            <div class="admin-user-detail-actions">
                <a href="{{ route('admin.gigs') }}">Back to gigs</a>
                @if (! $gig->trashed())
                    @can('gigs.publish')
                        <form method="POST" action="{{ route('admin.gigs.featured', $gig) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit">{{ $gig->featured ? 'Remove featured' : 'Feature gig' }}</button>
                        </form>
                    @endcan
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Moderation</h2>
                    <p>Actions stay on the inspection page.</p>
                </div>
            </div>

            @if ($gig->trashed())
                <p class="admin-empty-note">This gig was soft deleted by the seller. It stays visible here for admin review.</p>
            @else
                <div class="admin-detail-action-list">
                    @can('gigs.publish')
                        @foreach (['publish' => 'Publish', 'pause' => 'Pause'] as $action => $label)
                            <form method="POST" action="{{ route('admin.gigs.status', $gig) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="{{ $action }}">
                                <button type="submit">{{ $label }}</button>
                            </form>
                        @endforeach
                    @endcan
                    @can('gigs.review')
                        @foreach (['request_edits' => 'Request edits', 'reject' => 'Reject'] as $action => $label)
                            <form method="POST" action="{{ route('admin.gigs.status', $gig) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="{{ $action }}">
                                <button class="{{ $action === 'reject' ? 'is-danger' : '' }}" type="submit">{{ $label }}</button>
                            </form>
                        @endforeach
                    @endcan
                </div>
            @endif
        </aside>
    </section>

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Packages</h2>
                    <p>Scope and package pricing shown to buyers.</p>
                </div>
            </div>
            <div class="admin-card-list compact">
                @forelse ($packages as $package)
                    <article class="admin-mini-card">
                        <div>
                            <strong>{{ $package['name'] ?? $package['title'] ?? 'Package' }}</strong>
                            <p>{{ $package['description'] ?? 'No description' }}</p>
                        </div>
                        <div>
                            <b>${{ $package['price'] ?? '0' }}</b>
                            <span>{{ $package['delivery'] ?? 'No delivery' }}</span>
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-note">No packages are stored for this gig.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Requirements and extras</h2>
                    <p>Buyer inputs and optional add-ons.</p>
                </div>
            </div>
            <div class="admin-detail-columns">
                <section>
                    <h3>Requirements</h3>
                    <ul class="admin-plain-list">
                        @forelse ($requirements as $requirement)
                            <li>{{ $requirement['label'] ?? 'Requirement' }}{{ ($requirement['required'] ?? false) ? ' - required' : '' }}</li>
                        @empty
                            <li>No stored requirements.</li>
                        @endforelse
                    </ul>
                </section>
                <section>
                    <h3>Extras</h3>
                    <ul class="admin-plain-list">
                        @forelse ($extras as $extra)
                            <li>{{ $extra['label'] ?? $extra['title'] ?? 'Extra option' }}</li>
                        @empty
                            <li>No stored extras.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </article>
    </section>

    <section class="admin-detail-grid">
        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Gallery review</h2>
                    <p>Images persisted with the gig.</p>
                </div>
            </div>
            <div class="admin-detail-gallery">
                @forelse ($gallery as $image)
                    <img src="{{ $image }}" alt="{{ $gig->title }} gallery image">
                @empty
                    <p class="admin-empty-note">No gallery images are stored.</p>
                @endforelse
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Seller context</h2>
                    <p>Persisted labels and review notes.</p>
                </div>
            </div>
            <div class="admin-user-detail-meta">
                <section>
                    <h3>Seller details</h3>
                    <p>
                        @forelse ($sellerDetails as $detail)
                            <span>{{ $detail }}</span>
                        @empty
                            <em>No seller detail labels.</em>
                        @endforelse
                    </p>
                </section>
                <section>
                    <h3>Metadata</h3>
                    <p>
                        @forelse (collect($metadata)->except('about') as $key => $value)
                            <span>{{ str($key)->headline() }}: {{ is_scalar($value) ? $value : 'Stored' }}</span>
                        @empty
                            <em>No extra metadata.</em>
                        @endforelse
                    </p>
                </section>
            </div>
        </article>
    </section>
@endsection
