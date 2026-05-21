<?php

namespace App\Http\Controllers\Admin;

use App\Models\Conversation;

class DisputeController extends AdminController
{
    public function index()
    {
        $priorityConversationsQuery = Conversation::query()
            ->with(['buyer', 'seller'])
            ->where(function ($query) {
                $query
                    ->whereNotNull('priority')
                    ->orWhere('buyer_unread_count', '>', 0)
                    ->orWhere('seller_unread_count', '>', 0);
            })
            ->latest();
        $perPage = 8;
        $openCases = (clone $priorityConversationsQuery)->count();
        $pagination = $this->paginationMeta($openCases, $perPage);
        $priorityConversations = $priorityConversationsQuery
            ->skip(($pagination['currentPage'] - 1) * $perPage)
            ->take($perPage)
            ->get();

        $buyerWaiting = Conversation::where('buyer_unread_count', '>', 0)->count();
        $sellerWaiting = Conversation::where('seller_unread_count', '>', 0)->count();

        return $this->panelView('admin.pages.disputes', [
            'pageTitle' => 'Disputes',
            'pageEyebrow' => 'Resolution center',
            'pageDescription' => 'Prioritize buyer and seller conflicts with evidence, SLA, and refund visibility.',
            'searchPlaceholder' => 'Search disputes, orders, users',
            'pageActions' => [
                ['label' => 'Priority cases', 'route' => 'admin.disputes', 'meta' => number_format($openCases).' open'],
                ['label' => 'Order follow-up', 'route' => 'admin.orders', 'meta' => 'Delivery state'],
                ['label' => 'Refund review', 'route' => 'admin.payments', 'meta' => 'Part 3'],
            ],
            'stats' => [
                ['label' => 'Open cases', 'value' => number_format($openCases), 'meta' => 'Conversation-based'],
                ['label' => 'Awaiting buyer', 'value' => number_format($buyerWaiting), 'meta' => 'Unread buyer-side signals'],
                ['label' => 'Awaiting seller', 'value' => number_format($sellerWaiting), 'meta' => 'Unread seller-side signals'],
                ['label' => 'Resolved', 'value' => 'Part 3', 'meta' => 'Awaiting dispute table'],
            ],
            'disputes' => $priorityConversations
                ->map(fn (Conversation $conversation) => [
                    'case' => 'DSP-'.$conversation->id,
                    'order' => $conversation->public_id,
                    'reason' => $conversation->subject,
                    'owner' => $conversation->priority ?: 'Support',
                    'priority' => ($conversation->buyer_unread_count + $conversation->seller_unread_count) > 0 ? 'Needs reply' : 'Normal',
                ])
                ->all(),
            'pagination' => $pagination,
            'riskBuckets' => [
                ['label' => 'High-value refund risk', 'meta' => 'Part 3 dispute table', 'tone' => 'Critical'],
                ['label' => 'Late delivery dispute', 'meta' => number_format($openCases).' conversation signals', 'tone' => 'High'],
                ['label' => 'Scope disagreement', 'meta' => 'Review messages', 'tone' => 'Normal'],
            ],
        ]);
    }
}
