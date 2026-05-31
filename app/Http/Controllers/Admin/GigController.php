<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateAdminGigStatusRequest;
use App\Models\Admin;
use App\Models\Gig;
use App\Services\AdminGigModerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GigController extends AdminController
{
    private const STATUS_FILTERS = [
        'all' => ['label' => 'All', 'statuses' => []],
        'published' => ['label' => 'Published', 'statuses' => ['Live', 'Published', 'approved']],
        'review' => ['label' => 'Review', 'statuses' => ['draft', 'pending_review', 'Needs edit', 'review']],
        'paused' => ['label' => 'Paused', 'statuses' => ['Paused', 'paused']],
        'rejected' => ['label' => 'Rejected', 'statuses' => ['Rejected', 'rejected']],
        'deactivated' => ['label' => 'Deactivated', 'statuses' => ['Deactivated', 'deactivated']],
        'deleted' => ['label' => 'Deleted', 'statuses' => []],
    ];

    private const FEATURED_FILTERS = [
        'all' => 'All featured states',
        'featured' => 'Featured only',
        'not_featured' => 'Not featured',
    ];

    private const PRICE_FILTERS = [
        'all' => 'All prices',
        'under_50' => 'Under $50',
        '50_150' => '$50 to $149',
        '150_plus' => '$150+',
    ];

    private const DELIVERY_FILTERS = [
        'all' => 'Any delivery',
        'fast' => '1-2 days',
        'standard' => '3-7 days',
        'extended' => '8+ days',
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Newest first',
        'oldest' => 'Oldest first',
        'updated' => 'Recently updated',
        'price_high' => 'Price high to low',
        'price_low' => 'Price low to high',
        'title' => 'Title A-Z',
    ];

    private const BULK_STATUS_ACTIONS = [
        'approve',
        'pause',
        'deactivate',
        'reactivate',
        'request_edits',
        'reject',
    ];

    private const BULK_FEATURE_ACTIONS = [
        'feature',
        'unfeature',
    ];

    public function index(Request $request)
    {
        $filterState = $this->filterState($request);
        $status = $filterState['status'];

        $gigsQuery = ($status === 'deleted' ? Gig::onlyTrashed() : Gig::query())
            ->with('seller');

        $this->applyGigFilters($gigsQuery, $filterState);
        $this->applyGigSort($gigsQuery, $filterState['sort']);

        $perPage = 12;
        $total = (clone $gigsQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $gigs = $gigsQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (Gig $gig) => $this->gigRow($gig))
            ->all();

        $published = Gig::whereIn('status', ['Live', 'Published', 'approved'])->count();
        $pending = Gig::whereIn('status', ['draft', 'pending_review', 'Needs edit', 'review'])->count();
        $rejected = Gig::whereIn('status', ['Rejected', 'rejected'])->count();
        $featured = Gig::where('featured', true)->count();
        $deleted = Gig::onlyTrashed()->count();

        return $this->panelView('admin.pages.gigs', [
            'pageTitle' => 'Gigs',
            'pageEyebrow' => 'Catalog moderation',
            'pageDescription' => 'Review gig quality, publishing readiness, category fit, and content safety.',
            'searchPlaceholder' => 'Search gigs, categories, sellers',
            'stats' => [
                ['label' => 'Published gigs', 'value' => number_format($published), 'meta' => number_format(Gig::whereDate('created_at', now()->toDateString())->count()).' new today'],
                ['label' => 'Pending review', 'value' => number_format($pending), 'meta' => 'Need moderation'],
                ['label' => 'Rejected', 'value' => number_format($rejected), 'meta' => 'Current rejected listings'],
                ['label' => 'Featured gigs', 'value' => number_format($featured), 'meta' => 'Homepage rotation'],
            ],
            'gigs' => $gigs,
            'pagination' => $pagination,
            'filters' => $this->statusFilters(),
            'currentFilter' => $status,
            'searchQuery' => $filterState['q'],
            'filterState' => $filterState,
            'categoryOptions' => $this->categoryOptions(),
            'featuredFilters' => self::FEATURED_FILTERS,
            'priceFilters' => self::PRICE_FILTERS,
            'deliveryFilters' => self::DELIVERY_FILTERS,
            'sortOptions' => self::SORT_OPTIONS,
            'hasActiveFilters' => $this->hasActiveFilters($filterState),
            'categoryHealth' => $this->categoryHealth(),
            'rejectionReasons' => [
                ['label' => 'Unclear package scope', 'meta' => 'Use request edits', 'tone' => 'High'],
                ['label' => 'Low quality gallery image', 'meta' => 'Ask seller to replace', 'tone' => 'Medium'],
                ['label' => 'External contact details', 'meta' => 'Reject or request removal', 'tone' => 'Critical'],
            ],
        ]);
    }

    public function bulkAction(Request $request, AdminGigModerationService $moderation)
    {
        $payload = $request->validate([
            'bulk_action' => ['required', 'string', Rule::in($this->bulkActions())],
            'gigs' => ['required', 'array', 'min:1'],
            'gigs.*' => ['required', 'string', 'max:255', 'distinct'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = $payload['bulk_action'];
        $reason = trim((string) ($payload['reason'] ?? ''));

        if (in_array($action, ['reject', 'request_edits', 'deactivate'], true) && $reason === '') {
            return back()
                ->withInput()
                ->withErrors(['reason' => 'A moderation note is required for this bulk action.'])
                ->withNotify('error', 'Add a moderation note before applying this bulk action.', 'Bulk action needs a note');
        }

        $admin = $request->user('admin');
        abort_unless($this->adminCanRunBulkAction($admin, $action), 403);

        $selectedGigs = Gig::withTrashed()
            ->whereIn('slug', $payload['gigs'])
            ->get();

        $updated = 0;
        $skipped = max(0, count($payload['gigs']) - $selectedGigs->count());

        foreach ($selectedGigs as $gig) {
            if ($gig->trashed()) {
                $skipped++;

                continue;
            }

            if (in_array($action, self::BULK_FEATURE_ACTIONS, true)) {
                $featured = $action === 'feature';

                if ($gig->featured === $featured) {
                    $skipped++;

                    continue;
                }

                $moderation->setFeatured($gig, $featured, $admin);
                $updated++;

                continue;
            }

            $moderation->updateStatus($gig, $action, $admin, $reason !== '' ? $reason : null);
            $updated++;
        }

        if ($updated === 0) {
            return back()->withNotify('warning', 'No eligible gigs were changed by that bulk action.', 'Bulk action skipped');
        }

        $message = number_format($updated).' '.($updated === 1 ? 'gig' : 'gigs').' updated.';

        if ($skipped > 0) {
            $message .= ' '.number_format($skipped).' skipped.';
        }

        return back()->withNotify('success', $message, 'Bulk action applied');
    }

    public function show(string $gig)
    {
        $gig = Gig::withTrashed()
            ->with('seller')
            ->where('slug', $gig)
            ->firstOrFail();
        $gig->loadCount(['orders', 'savedByUsers']);

        return $this->panelView('admin.pages.gig-details', [
            'pageTitle' => $gig->title,
            'pageEyebrow' => 'Gig details',
            'pageDescription' => 'Inspect service content, package scope, gallery quality, and moderation state before acting.',
            'searchPlaceholder' => 'Search gigs, categories, sellers',
            'gig' => $gig,
            'stats' => [
                ['label' => 'Starting price', 'value' => $this->money((int) $gig->price_cents), 'meta' => $gig->delivery_days.' day delivery'],
                ['label' => 'Saved by users', 'value' => number_format($gig->saved_by_users_count), 'meta' => 'Marketplace shortlists'],
                ['label' => 'Orders', 'value' => number_format($gig->orders_count), 'meta' => 'Linked order records'],
                ['label' => 'Reviews', 'value' => number_format((int) $gig->reviews), 'meta' => 'Rating '.number_format((float) $gig->rating, 1)],
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminGigStatusRequest $request,
        Gig $gig,
        AdminGigModerationService $moderation
    ) {
        $payload = $request->validated();
        $gig = $moderation->updateStatus(
            $gig,
            $payload['action'],
            $request->user('admin'),
            $payload['reason'] ?? null,
        );

        return back()->withNotify('success', 'Gig status updated to '.$gig->status.'.', 'Gig updated');
    }

    public function toggleFeatured(Request $request, Gig $gig, AdminGigModerationService $moderation)
    {
        $gig = $moderation->toggleFeatured($gig, $request->user('admin'));

        return back()->withNotify(
            'success',
            $gig->featured ? 'Gig added to featured services.' : 'Gig removed from featured services.',
            'Featured gigs updated',
        );
    }

    private function gigRow(Gig $gig): array
    {
        return [
            'id' => $gig->slug,
            'title' => $gig->title,
            'seller' => $gig->seller?->name ?: $gig->seller_name,
            'seller_email' => $gig->seller?->email,
            'category' => $gig->category_label ?: 'Uncategorized',
            'price' => $this->money((int) $gig->price_cents),
            'delivery' => ((int) $gig->delivery_days).' '.((int) $gig->delivery_days === 1 ? 'day' : 'days'),
            'rating' => number_format((float) $gig->rating, 1),
            'reviews' => number_format((int) $gig->reviews),
            'status' => $gig->status,
            'status_class' => $gig->trashed() ? 'status-cancelled' : $this->gigStatusClass($gig->status),
            'featured' => $gig->featured,
            'deleted' => $gig->trashed(),
            'updated' => $gig->updated_at?->diffForHumans() ?? 'Unknown',
        ];
    }

    private function filterState(Request $request): array
    {
        $status = $this->validatedOption($request->query('status', 'all'), array_keys(self::STATUS_FILTERS), 'all');
        $featured = $this->validatedOption($request->query('featured', 'all'), array_keys(self::FEATURED_FILTERS), 'all');
        $price = $this->validatedOption($request->query('price', 'all'), array_keys(self::PRICE_FILTERS), 'all');
        $delivery = $this->validatedOption($request->query('delivery', 'all'), array_keys(self::DELIVERY_FILTERS), 'all');
        $sort = $this->validatedOption($request->query('sort', 'latest'), array_keys(self::SORT_OPTIONS), 'latest');

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $status,
            'category' => trim((string) $request->query('category', '')),
            'seller' => trim((string) $request->query('seller', '')),
            'featured' => $featured,
            'price' => $price,
            'delivery' => $delivery,
            'sort' => $sort,
        ];
    }

    private function applyGigFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'deleted') {
            $statuses = self::STATUS_FILTERS[$filters['status']]['statuses'] ?? [];

            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('seller_name', 'like', "%{$search}%")
                    ->orWhere('category_label', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('seller', function (Builder $sellerQuery) use ($search) {
                        $sellerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters['seller'] !== '') {
            $seller = $filters['seller'];

            $query->where(function (Builder $query) use ($seller) {
                $query
                    ->where('seller_name', 'like', "%{$seller}%")
                    ->orWhereHas('seller', function (Builder $sellerQuery) use ($seller) {
                        $sellerQuery
                            ->where('name', 'like', "%{$seller}%")
                            ->orWhere('username', 'like', "%{$seller}%")
                            ->orWhere('email', 'like', "%{$seller}%");
                    });
            });
        }

        if ($filters['category'] !== '') {
            $query->where('category_label', $filters['category']);
        }

        match ($filters['featured']) {
            'featured' => $query->where('featured', true),
            'not_featured' => $query->where('featured', false),
            default => null,
        };

        match ($filters['price']) {
            'under_50' => $query->where('price_cents', '<', 5000),
            '50_150' => $query->whereBetween('price_cents', [5000, 14999]),
            '150_plus' => $query->where('price_cents', '>=', 15000),
            default => null,
        };

        match ($filters['delivery']) {
            'fast' => $query->where('delivery_days', '<=', 2),
            'standard' => $query->whereBetween('delivery_days', [3, 7]),
            'extended' => $query->where('delivery_days', '>=', 8),
            default => null,
        };
    }

    private function applyGigSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'updated' => $query->latest('updated_at'),
            'price_high' => $query->orderByDesc('price_cents')->latest(),
            'price_low' => $query->orderBy('price_cents')->latest(),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };
    }

    private function statusFilters(): array
    {
        return collect(self::STATUS_FILTERS)
            ->map(fn (array $filter, string $value) => [
                'label' => $filter['label'],
                'value' => $value,
                'count' => $this->statusFilterCount($value),
            ])
            ->values()
            ->all();
    }

    private function statusFilterCount(string $status): int
    {
        if ($status === 'all') {
            return Gig::count();
        }

        if ($status === 'deleted') {
            return Gig::onlyTrashed()->count();
        }

        $statuses = self::STATUS_FILTERS[$status]['statuses'] ?? [];

        if ($statuses === []) {
            return 0;
        }

        return Gig::whereIn('status', $statuses)->count();
    }

    private function categoryOptions(): array
    {
        return Gig::withTrashed()
            ->whereNotNull('category_label')
            ->where('category_label', '<>', '')
            ->distinct()
            ->orderBy('category_label')
            ->pluck('category_label')
            ->all();
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['status'] !== 'all'
            || $filters['category'] !== ''
            || $filters['seller'] !== ''
            || $filters['featured'] !== 'all'
            || $filters['price'] !== 'all'
            || $filters['delivery'] !== 'all'
            || $filters['sort'] !== 'latest';
    }

    private function bulkActions(): array
    {
        return [
            ...self::BULK_STATUS_ACTIONS,
            ...self::BULK_FEATURE_ACTIONS,
        ];
    }

    private function adminCanRunBulkAction(?Admin $admin, string $action): bool
    {
        if (! $admin) {
            return false;
        }

        if (in_array($action, ['request_edits', 'reject'], true)) {
            return $admin->can('gigs.review');
        }

        return $admin->can('gigs.publish');
    }

    private function validatedOption(mixed $value, array $allowed, string $fallback): string
    {
        $value = trim((string) $value);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function categoryHealth(): array
    {
        $total = max(1, Gig::count());

        return Gig::query()
            ->selectRaw('COALESCE(category_label, "Uncategorized") as category')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->take(4)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->category,
                'value' => (int) round(($row->total / $total) * 100),
            ])
            ->all();
    }
}
