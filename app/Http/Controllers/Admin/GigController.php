<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateAdminGigStatusRequest;
use App\Models\Gig;
use App\Services\AdminGigModerationService;
use Illuminate\Http\Request;

class GigController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'published', 'review', 'paused', 'rejected', 'deleted'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $gigsQuery = ($status === 'deleted' ? Gig::onlyTrashed() : Gig::query())
            ->with('seller')
            ->latest();

        if ($search !== '') {
            $gigsQuery->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('seller_name', 'like', "%{$search}%")
                    ->orWhere('category_label', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        match ($status) {
            'published' => $gigsQuery->whereIn('status', ['Live', 'Published']),
            'review' => $gigsQuery->whereNotIn('status', ['Live', 'Published', 'Paused', 'Rejected']),
            'paused' => $gigsQuery->where('status', 'Paused'),
            'rejected' => $gigsQuery->where('status', 'Rejected'),
            default => null,
        };

        $perPage = 8;
        $total = (clone $gigsQuery)->count();
        $pagination = $this->paginationMeta($total, $perPage);
        $gigs = $gigsQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(fn (Gig $gig) => $this->gigRow($gig))
            ->all();

        $published = Gig::whereIn('status', ['Live', 'Published'])->count();
        $pending = Gig::whereNotIn('status', ['Live', 'Published', 'Paused', 'Rejected'])->count();
        $rejected = Gig::where('status', 'Rejected')->count();
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
            'filters' => [
                ['label' => 'All', 'value' => 'all', 'count' => Gig::count()],
                ['label' => 'Published', 'value' => 'published', 'count' => $published],
                ['label' => 'Review', 'value' => 'review', 'count' => $pending],
                ['label' => 'Paused', 'value' => 'paused', 'count' => Gig::where('status', 'Paused')->count()],
                ['label' => 'Rejected', 'value' => 'rejected', 'count' => $rejected],
                ['label' => 'Deleted', 'value' => 'deleted', 'count' => $deleted],
            ],
            'currentFilter' => $status,
            'searchQuery' => $search,
            'categoryHealth' => $this->categoryHealth(),
            'rejectionReasons' => [
                ['label' => 'Unclear package scope', 'meta' => 'Use request edits', 'tone' => 'High'],
                ['label' => 'Low quality gallery image', 'meta' => 'Ask seller to replace', 'tone' => 'Medium'],
                ['label' => 'External contact details', 'meta' => 'Reject or request removal', 'tone' => 'Critical'],
            ],
        ]);
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
        $gig = $moderation->updateStatus($gig, $request->validated()['action']);

        return back()->withNotify('success', 'Gig status updated to '.$gig->status.'.', 'Gig updated');
    }

    public function toggleFeatured(Request $request, Gig $gig, AdminGigModerationService $moderation)
    {
        $gig = $moderation->toggleFeatured($gig);

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
            'category' => $gig->category_label ?: 'Uncategorized',
            'price' => $this->money((int) $gig->price_cents),
            'status' => $gig->status,
            'status_class' => $gig->trashed() ? 'status-cancelled' : $this->gigStatusClass($gig->status),
            'featured' => $gig->featured,
            'deleted' => $gig->trashed(),
            'updated' => $gig->updated_at?->diffForHumans() ?? 'Unknown',
        ];
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
