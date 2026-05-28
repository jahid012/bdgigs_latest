<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Gig;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardSummaryService
{
    public function for(User $user, string $variant): array
    {
        return $variant === 'seller'
            ? $this->sellerSummary($user)
            : $this->buyerSummary($user);
    }

    private function buyerSummary(User $user): array
    {
        $orders = $user->buyerOrders()->latest()->get();
        $conversations = $this->conversationsFor($user);
        $activeOrders = $orders->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']);
        $completedOrders = $orders->whereIn('status', ['Delivered', 'Completed']);
        $spent = (int) $orders->sum('price_cents');
        $savedCount = $user->savedServices()->count();

        return [
            'variant' => 'buyer',
            'stats' => [
                $this->stat('Active Orders', $activeOrders->count(), $this->plural($activeOrders->count(), 'open project', 'open projects'), 'orders'),
                $this->stat('Completed Jobs', $completedOrders->count(), $this->plural($completedOrders->count(), 'finished order', 'finished orders'), 'packageCheck'),
                $this->stat('Total Spent', $this->money($spent), 'From placed orders', 'payment'),
                $this->stat('Saved Services', $savedCount, $this->plural($savedCount, 'shortlist item', 'shortlist items'), 'heart'),
            ],
            'highlights' => [
                ['label' => 'Unread messages', 'value' => (string) $this->unreadCount($user)],
                ['label' => 'Next delivery', 'value' => $this->nextDueLabel($activeOrders)],
                ['label' => 'Saved services', 'value' => (string) $savedCount],
            ],
            'chartData' => $this->monthlySeries($orders, 'price_cents'),
            'orders' => $orders->take(5)->map(fn (Order $order) => $this->orderRow($order, 'buyer'))->values(),
            'messages' => $this->messagePreviews($conversations),
            'recommendedServices' => Gig::query()
                ->where('seller_id', '!=', $user->id)
                ->latest()
                ->take(4)
                ->get()
                ->map(fn (Gig $gig) => $this->recommendedGig($gig))
                ->values(),
        ];
    }

    private function sellerSummary(User $user): array
    {
        $orders = $user->sellerOrders()->latest()->get();
        $conversations = $this->conversationsFor($user);
        $activeOrders = $orders->whereNotIn('status', ['Delivered', 'Completed', 'Cancelled']);
        $completedOrders = $orders->whereIn('status', ['Delivered', 'Completed']);
        $monthEarnings = (int) $orders
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('earnings_cents');
        $sellerGigs = $user->gigs()->latest()->take(4)->get();

        return [
            'variant' => 'seller',
            'stats' => [
                $this->stat('Active Orders', $activeOrders->count(), $this->plural($activeOrders->count(), 'delivery open', 'deliveries open'), 'orders'),
                $this->stat('Completed Jobs', $completedOrders->count(), $this->plural($completedOrders->count(), 'finished order', 'finished orders'), 'packageCheck'),
                $this->stat('Monthly Revenue', $this->money($monthEarnings), 'From seller orders', 'payment'),
                $this->stat('Unread Messages', $this->unreadCount($user), $this->plural($this->unreadCount($user), 'thread update', 'thread updates'), 'message'),
            ],
            'highlights' => [
                ['label' => 'Live gigs', 'value' => (string) $user->gigs()->whereIn('status', ['Live', 'Published', 'approved'])->count()],
                ['label' => 'Next due', 'value' => $this->nextDueLabel($activeOrders)],
                ['label' => 'Active buyers', 'value' => (string) $activeOrders->pluck('buyer_id')->filter()->unique()->count()],
            ],
            'chartData' => $this->monthlySeries($orders, 'earnings_cents'),
            'orders' => $orders->take(5)->map(fn (Order $order) => $this->orderRow($order, 'seller'))->values(),
            'messages' => $this->messagePreviews($conversations),
            'pipeline' => $activeOrders
                ->sortBy('due_date')
                ->take(4)
                ->map(fn (Order $order) => [
                    'title' => $order->service,
                    'detail' => $order->buyer_name ? "Buyer: {$order->buyer_name}" : 'Buyer order',
                    'progress' => $this->progressFor($order->status),
                    'due' => $order->due_date?->format('M j') ?? 'No due date',
                ])
                ->values(),
            'sellerServices' => $sellerGigs->map(fn (Gig $gig) => $this->sellerService($gig))->values(),
        ];
    }

    private function conversationsFor(User $user): Collection
    {
        return Conversation::query()
            ->with(['messages', 'participants.user'])
            ->whereHas('participants', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNull('archived_at'))
            ->latest('last_message_at')
            ->latest()
            ->take(5)
            ->get();
    }

    private function messagePreviews(Collection $conversations): Collection
    {
        return $conversations
            ->map(function (Conversation $conversation) {
                $lastMessage = $conversation->messages->last();
                $name = $conversation->participants
                    ->first(fn ($participant) => $participant->user_id !== auth()->id())
                    ?->user?->name
                    ?: ($lastMessage?->sender_name ?: 'Conversation');

                return [
                    'initials' => collect(explode(' ', trim($name)))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part) => mb_substr($part, 0, 1))
                        ->implode(''),
                    'name' => $name,
                    'message' => $lastMessage?->body ?: '',
                    'time' => $lastMessage?->sent_at?->diffForHumans(short: true)
                        ?? $conversation->updated_at->diffForHumans(short: true),
                ];
            })
            ->values();
    }

    private function orderRow(Order $order, string $role): array
    {
        return [
            'id' => '#'.$order->code,
            'service' => $order->service,
            'seller' => $order->seller_name,
            'buyer' => $order->buyer_name,
            'status' => $order->status,
            'statusClass' => $order->status_class,
            'dueDate' => $order->due_date?->format('M j, Y'),
            'price' => $this->money($order->price_cents),
            'earnings' => $this->money($order->earnings_cents),
            'role' => $role,
        ];
    }

    private function recommendedGig(Gig $gig): array
    {
        return [
            'id' => $gig->slug,
            'title' => $gig->title,
            'seller' => $gig->seller_name,
            'rating' => number_format((float) $gig->rating, 1),
            'price' => $this->money($gig->price_cents),
            'image' => $gig->image,
            'tag' => $gig->tag ?: ($gig->category_label ?: 'Service'),
            'delivery' => $this->plural($gig->delivery_days, 'day', 'days'),
        ];
    }

    private function sellerService(Gig $gig): array
    {
        return [
            'id' => $gig->slug,
            'title' => $gig->title,
            'category' => $gig->category_label ?: 'Service',
            'rating' => number_format((float) $gig->rating, 1),
            'price' => $this->money($gig->price_cents),
            'image' => $gig->image,
            'tag' => $gig->tag ?: 'Gig',
            'delivery' => $this->plural($gig->delivery_days, 'day', 'days'),
            'orders' => $gig->orders_label,
            'conversion' => $gig->conversion_label,
            'status' => $gig->status,
            'statusClass' => $gig->status_class,
        ];
    }

    private function monthlySeries(Collection $orders, string $column): array
    {
        return collect(range(6, 0))
            ->map(function (int $offset) use ($orders, $column) {
                $month = now()->subMonths($offset);
                $value = (int) round($orders
                    ->filter(fn (Order $order) => $order->created_at?->isSameMonth($month))
                    ->sum($column) / 100);

                return [
                    'label' => Carbon::parse($month)->format('M'),
                    'value' => $value,
                ];
            })
            ->all();
    }

    private function unreadCount(User $user): int
    {
        return (int) $user->conversationParticipants()->sum('unread_count');
    }

    private function nextDueLabel(Collection $orders): string
    {
        $next = $orders
            ->filter(fn (Order $order) => $order->due_date)
            ->sortBy('due_date')
            ->first();

        return $next?->due_date?->format('M j') ?? 'None';
    }

    private function stat(string $label, int|string $value, string $trend, string $icon): array
    {
        return compact('label', 'value', 'trend', 'icon');
    }

    private function progressFor(string $status): int
    {
        return match ($status) {
            'Delivered' => 88,
            'Completed' => 100,
            'Cancelled' => 18,
            default => 62,
        };
    }

    private function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 0);
    }

    private function plural(int $count, string $single, string $plural): string
    {
        return "{$count} ".($count === 1 ? $single : $plural);
    }
}
