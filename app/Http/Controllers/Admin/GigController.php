<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gig;
use Illuminate\Http\Request;

class GigController extends AdminController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'published', 'review', 'paused', 'rejected'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $gigsQuery = Gig::query()
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

        return $this->panelView('admin.pages.gigs', [
            'pageTitle' => 'Gigs',
            'pageEyebrow' => 'Catalog moderation',
            'pageDescription' => 'Review gig quality, publishing readiness, category fit, and content safety.',
            'searchPlaceholder' => 'Search gigs, categories, sellers',
            'pageActions' => [
                ['label' => 'Review queue', 'route' => 'admin.gigs', 'meta' => number_format($pending).' pending'],
                ['label' => 'Featured rotation', 'route' => 'admin.gigs', 'meta' => number_format($featured).' active'],
                ['label' => 'Category audit', 'route' => 'admin.gigs', 'meta' => number_format(Gig::whereNotNull('category_label')->distinct()->count('category_label')).' categories'],
            ],
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

    public function updateStatus(Request $request, Gig $gig)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:publish,pause,reject,request_edits'],
        ]);

        if (in_array($data['action'], ['publish', 'pause'], true) && ! $request->user()->can('gigs.publish')) {
            abort(403);
        }

        if (in_array($data['action'], ['reject', 'request_edits'], true) && ! $request->user()->can('gigs.review')) {
            abort(403);
        }

        $status = match ($data['action']) {
            'publish' => 'Published',
            'pause' => 'Paused',
            'reject' => 'Rejected',
            'request_edits' => 'Needs edit',
        };

        $gig->forceFill([
            'status' => $status,
            'status_class' => $this->gigStatusClass($status),
        ])->save();

        return back()->withNotify('success', 'Gig status updated to '.$status.'.', 'Gig updated');
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
            'status_class' => $this->gigStatusClass($gig->status),
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
