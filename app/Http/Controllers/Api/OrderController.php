<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DecideOrderTimeExtensionRequest;
use App\Http\Requests\Api\DecideOrderCancellationRequest;
use App\Http\Requests\Api\RequestOrderRevisionRequest;
use App\Http\Requests\Api\RequestOrderCancellationRequest;
use App\Http\Requests\Api\StoreDisputeMessageRequest;
use App\Http\Requests\Api\StoreOrderDisputeEvidenceRequest;
use App\Http\Requests\Api\StoreOrderDisputeRequest;
use App\Http\Requests\Api\StoreOrderPrivateNoteRequest;
use App\Http\Requests\Api\StoreOrderReviewRequest;
use App\Http\Requests\Api\StoreOrderTimeExtensionRequest;
use App\Http\Requests\Api\SubmitOrderDeliveryRequest;
use App\Http\Requests\Api\SubmitOrderRequirementsRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderReceiptResource;
use App\Http\Resources\OrderResource;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderPrivateNote;
use App\Models\OrderTimeExtensionRequest;
use App\Services\OrderDisputeService;
use App\Services\OrderInvoiceService;
use App\Services\OrderCancellationService;
use App\Services\OrderLifecycleService;
use App\Services\OrderPrivateNoteService;
use App\Services\OrderRequirementService;
use App\Services\OrderReviewService;
use App\Services\OrderTimeExtensionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $role = $request->query('role') === 'seller' ? 'seller' : 'buyer';
        $column = $role === 'seller' ? 'seller_id' : 'buyer_id';

        return OrderResource::collection(
            Order::query()
                ->where($column, $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function show(Request $request, Order $order): OrderDetailResource
    {
        $role = $request->query('role') === 'seller' ? 'seller' : 'buyer';
        $ownerColumn = $role === 'seller' ? 'seller_id' : 'buyer_id';

        abort_unless(
            $order->{$ownerColumn} === $request->user()->id ||
                $request->user()->can('orders.view') ||
                $request->user()->can('orders.manage'),
            403,
        );

        return $this->detailResponse($request, $order);
    }

    public function requestTimeExtension(
        StoreOrderTimeExtensionRequest $request,
        Order $order,
        OrderTimeExtensionService $extensions
    ): OrderDetailResource {
        $extensions->request($order->loadMissing(['buyer', 'seller']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function decideTimeExtension(
        DecideOrderTimeExtensionRequest $request,
        Order $order,
        OrderTimeExtensionRequest $extension,
        OrderTimeExtensionService $extensions
    ): OrderDetailResource {
        $extensions->decide(
            $order->loadMissing(['buyer', 'seller']),
            $extension,
            $request->user(),
            $request->validated()['decision'],
        );

        return $this->detailResponse($request, $order->refresh());
    }

    public function storePrivateNote(
        StoreOrderPrivateNoteRequest $request,
        Order $order,
        OrderPrivateNoteService $notes
    ): OrderDetailResource {
        $notes->create($order, $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function updatePrivateNote(
        StoreOrderPrivateNoteRequest $request,
        Order $order,
        OrderPrivateNote $note,
        OrderPrivateNoteService $notes
    ): OrderDetailResource {
        $notes->update($order, $note, $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function destroyPrivateNote(
        Request $request,
        Order $order,
        OrderPrivateNote $note,
        OrderPrivateNoteService $notes
    ): OrderDetailResource {
        $notes->delete($order, $note, $request->user());

        return $this->detailResponse($request, $order->refresh());
    }

    public function storeDispute(
        StoreOrderDisputeRequest $request,
        Order $order,
        OrderDisputeService $disputes
    ): OrderDetailResource {
        $disputes->open($order->loadMissing(['buyer', 'seller']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function storeDisputeMessage(
        StoreDisputeMessageRequest $request,
        Order $order,
        Dispute $dispute,
        OrderDisputeService $disputes
    ): OrderDetailResource {
        $disputes->message($order->loadMissing(['buyer', 'seller']), $dispute, $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function storeDisputeEvidence(
        StoreOrderDisputeEvidenceRequest $request,
        Order $order,
        Dispute $dispute,
        OrderDisputeService $disputes
    ): OrderDetailResource {
        $disputes->evidence($order->loadMissing(['buyer', 'seller']), $dispute, $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function storeReview(
        StoreOrderReviewRequest $request,
        Order $order,
        OrderReviewService $reviews
    ): OrderDetailResource {
        $reviews->submit($order->loadMissing(['buyer', 'seller', 'gig', 'activities']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function submitRequirements(
        SubmitOrderRequirementsRequest $request,
        Order $order,
        OrderRequirementService $requirements
    ): OrderDetailResource {
        $requirements->submit($order->loadMissing(['buyer', 'seller', 'gig']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function submitDelivery(
        SubmitOrderDeliveryRequest $request,
        Order $order,
        OrderLifecycleService $lifecycle
    ): OrderDetailResource {
        $lifecycle->submitDelivery($order->loadMissing(['buyer', 'seller', 'gig']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function startWork(
        Request $request,
        Order $order,
        OrderLifecycleService $lifecycle
    ): OrderDetailResource {
        $lifecycle->startWork($order->loadMissing(['buyer', 'seller']), $request->user());

        return $this->detailResponse($request, $order->refresh());
    }

    public function requestRevision(
        RequestOrderRevisionRequest $request,
        Order $order,
        OrderLifecycleService $lifecycle
    ): OrderDetailResource {
        $lifecycle->requestRevision($order->loadMissing(['buyer', 'seller']), $request->user(), $request->validated());

        return $this->detailResponse($request, $order->refresh());
    }

    public function complete(
        Request $request,
        Order $order,
        OrderLifecycleService $lifecycle
    ): OrderDetailResource {
        $lifecycle->complete($order->loadMissing(['buyer', 'seller']), $request->user());

        return $this->detailResponse($request, $order->refresh());
    }

    public function requestCancellation(
        RequestOrderCancellationRequest $request,
        Order $order,
        OrderCancellationService $cancellations
    ): OrderDetailResource {
        $payload = $request->validated();

        $cancellations->request($order->loadMissing(['buyer', 'seller', 'latestCancellation']), $request->user(), $payload['reason']);

        return $this->detailResponse($request, $order->refresh());
    }

    public function decideCancellation(
        DecideOrderCancellationRequest $request,
        Order $order,
        OrderCancellationService $cancellations
    ): OrderDetailResource {
        $payload = $request->validated();

        $payload['decision'] === 'accept'
            ? $cancellations->accept($order->loadMissing(['buyer', 'seller', 'latestCancellation']), $request->user(), $payload['note'] ?? null)
            : $cancellations->reject($order->loadMissing(['buyer', 'seller', 'latestCancellation']), $request->user(), $payload['note'] ?? null);

        return $this->detailResponse($request, $order->refresh());
    }

    public function receipt(Request $request, Order $order, OrderInvoiceService $invoices)
    {
        abort_unless(
            $order->buyer_id === $request->user()->id ||
                $order->seller_id === $request->user()->id ||
                $request->user()->can('orders.view') ||
                $request->user()->can('orders.manage'),
            403,
        );

        $invoice = $order->invoice ?: $invoices->generate($order->loadMissing(['buyer', 'seller', 'gig']));

        $payload = $invoices->payload($invoice->fresh());

        if (! $request->expectsJson()) {
            return response()->view('receipts.order', ['receipt' => $payload]);
        }

        return OrderReceiptResource::make($payload)->response();
    }

    private function detailResponse(Request $request, Order $order): OrderDetailResource
    {
        $order->loadMissing([
            'buyer',
            'seller',
            'gig',
            'activities.actor',
            'activities.adminActor',
            'invoice',
            'latestCancellation.requester',
            'latestCancellation.responder',
            'manualPaymentSubmission.method',
            'manualPaymentSubmission.adminReviewer',
            'timeExtensionRequests.requester',
            'timeExtensionRequests.reviewer',
            'disputes.openedBy',
            'disputes.openedByAdmin',
            'disputes.activities.actor',
            'disputes.activities.adminActor',
            'reviews.reviewer',
            'reviews.reviewee',
        ]);
        $order->load([
            'privateNotes' => fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->latest(),
        ]);

        return OrderDetailResource::make($order);
    }
}
