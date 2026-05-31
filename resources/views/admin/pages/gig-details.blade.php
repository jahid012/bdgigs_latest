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
        $moderationActions = [
            [
                'action' => 'approve',
                'label' => 'Approve',
                'title' => 'Approve this gig',
                'description' => 'Publish this gig and notify the seller that it is approved.',
                'permission' => 'gigs.publish',
                'requires_reason' => false,
                'shows_reason' => false,
                'tone' => 'positive',
            ],
            [
                'action' => 'pause',
                'label' => 'Pause',
                'title' => 'Pause this gig',
                'description' => 'Temporarily pause this gig while the listing is reviewed or corrected.',
                'permission' => 'gigs.publish',
                'requires_reason' => false,
                'shows_reason' => true,
                'tone' => 'neutral',
            ],
            [
                'action' => 'deactivate',
                'label' => 'Deactivate',
                'title' => 'Deactivate this gig',
                'description' => 'Remove this gig from active marketplace availability until it is reactivated.',
                'permission' => 'gigs.publish',
                'requires_reason' => true,
                'shows_reason' => true,
                'tone' => 'warning',
            ],
            [
                'action' => 'reactivate',
                'label' => 'Reactivate',
                'title' => 'Reactivate this gig',
                'description' => 'Return this gig to approved marketplace status.',
                'permission' => 'gigs.publish',
                'requires_reason' => false,
                'shows_reason' => false,
                'tone' => 'positive',
            ],
            [
                'action' => 'request_edits',
                'label' => 'Request edits',
                'title' => 'Request seller edits',
                'description' => 'Send this gig back to the seller with a clear note about what needs to change.',
                'permission' => 'gigs.review',
                'requires_reason' => true,
                'shows_reason' => true,
                'tone' => 'neutral',
            ],
            [
                'action' => 'reject',
                'label' => 'Reject',
                'title' => 'Reject this gig',
                'description' => 'Reject this gig and store the moderation reason for audit and seller guidance.',
                'permission' => 'gigs.review',
                'requires_reason' => true,
                'shows_reason' => true,
                'tone' => 'danger',
            ],
        ];
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
                <div class="admin-detail-copy">
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
                        <form class="admin-feature-toggle-form" method="POST" action="{{ route('admin.gigs.featured', $gig) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                class="admin-toggle-button {{ $gig->featured ? 'is-on' : '' }}"
                                type="submit"
                                role="switch"
                                aria-checked="{{ $gig->featured ? 'true' : 'false' }}"
                            >
                                <span aria-hidden="true"></span>
                                <strong>{{ $gig->featured ? 'Featured on' : 'Featured off' }}</strong>
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </article>

        <aside class="admin-panel admin-detail-action-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Moderation</h2>
                    <p>Open an action, review the impact, then confirm.</p>
                </div>
            </div>

            @if ($gig->trashed())
                <p class="admin-empty-note">This gig was soft deleted by the seller. It stays visible here for admin review.</p>
            @else
                <div class="admin-moderation-summary">
                    <span><strong>{{ $gig->moderated_at?->diffForHumans() ?? 'Not moderated yet' }}</strong>Last decision</span>
                    <span><strong>{{ $gig->moderation_reason ? 'Has note' : 'No note' }}</strong>Moderation reason</span>
                </div>
                <div class="admin-moderation-action-list">
                    @foreach ($moderationActions as $moderationAction)
                        @can($moderationAction['permission'])
                            @php($modalId = 'gig-moderation-'.str_replace('_', '-', $moderationAction['action']))
                            <button
                                class="admin-moderation-action-button is-{{ $moderationAction['tone'] }}"
                                type="button"
                                data-admin-modal-open="{{ $modalId }}"
                            >
                                <strong>{{ $moderationAction['label'] }}</strong>
                                <span>{{ $moderationAction['description'] }}</span>
                            </button>
                        @endcan
                    @endforeach
                </div>
            @endif
        </aside>
    </section>

    @if (! $gig->trashed())
        @foreach ($moderationActions as $moderationAction)
            @can($moderationAction['permission'])
                @php($modalId = 'gig-moderation-'.str_replace('_', '-', $moderationAction['action']))
                <dialog class="admin-modal" id="{{ $modalId }}" data-admin-modal data-admin-moderation-modal>
                    <div class="admin-modal-panel">
                        <div class="admin-modal-head">
                            <div>
                                <p class="admin-eyebrow">Gig moderation</p>
                                <h2>{{ $moderationAction['title'] }}</h2>
                                <span>{{ $moderationAction['description'] }}</span>
                            </div>
                            <button type="button" data-admin-modal-close aria-label="Close {{ $moderationAction['label'] }} modal">Close</button>
                        </div>
                        <form class="admin-detail-form admin-modal-form" method="POST" action="{{ route('admin.gigs.status', $gig) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="{{ $moderationAction['action'] }}">

                            @if ($moderationAction['shows_reason'])
                                <label>
                                    <span>Moderation note{{ $moderationAction['requires_reason'] ? ' (required)' : ' (optional)' }}</span>
                                    <textarea
                                        name="reason"
                                        rows="4"
                                        placeholder="Add clear seller-facing context for this decision"
                                        @if ($moderationAction['requires_reason']) required @endif
                                    ></textarea>
                                </label>
                            @else
                                <p class="admin-modal-note">This action does not require a note, but it will be saved in the moderation timeline.</p>
                            @endif

                            <div class="admin-modal-actions">
                                <button type="button" class="admin-secondary-button" data-admin-modal-close>Cancel</button>
                                <button class="{{ $moderationAction['tone'] === 'danger' ? 'is-danger' : '' }}" type="submit">
                                    Confirm {{ strtolower($moderationAction['label']) }}
                                </button>
                            </div>
                        </form>
                    </div>
                </dialog>
            @endcan
        @endforeach

        @include('admin.partials.modal-scripts')
    @endif

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
