<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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

        abort_unless($order->{$ownerColumn} === $request->user()->id, 403);

        return OrderDetailResource::make($order->loadMissing(['buyer', 'seller', 'gig']));
    }
}
