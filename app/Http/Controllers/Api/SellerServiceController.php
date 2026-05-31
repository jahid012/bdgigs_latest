<?php

namespace App\Http\Controllers\Api;

use App\Events\GigCreated;
use App\Events\GigEdited;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSellerServiceRequest;
use App\Http\Requests\Api\UpdateSellerServiceRequest;
use App\Http\Requests\Api\UpdateSellerServiceStatusRequest;
use App\Http\Resources\SellerServiceResource;
use App\Models\Gig;
use App\Services\GigMediaSyncService;
use App\Services\SellerServicePayloadBuilder;
use App\Services\SellerGigLifecycleService;
use App\Support\MarketplaceNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SellerServiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SellerServiceResource::collection(
            Gig::query()
                ->with('media')
                ->where('seller_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function store(
        StoreSellerServiceRequest $request,
        MarketplaceNotifier $notifier,
        GigMediaSyncService $mediaSync,
        SellerServicePayloadBuilder $payloadBuilder
    ): SellerServiceResource
    {
        $payload = $request->validated();

        $gig = Gig::create($payloadBuilder->attributes($payload, $request->user()));
        $gig = $mediaSync->sync($gig, $payload['media'] ?? [], $payload['galleryImages'] ?? []);
        event(new GigCreated($gig->fresh(['seller', 'media'])));

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig draft saved',
            "{$gig->title} is now available in your seller services.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig);
    }

    public function show(Request $request, Gig $gig): SellerServiceResource
    {
        $this->authorizeSeller($request, $gig);

        return SellerServiceResource::make($gig->load('media'));
    }

    public function update(
        UpdateSellerServiceRequest $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        GigMediaSyncService $mediaSync,
        SellerServicePayloadBuilder $payloadBuilder
    ): SellerServiceResource
    {
        $this->authorizeSeller($request, $gig);

        $payload = $request->validated();

        $gig->update($payloadBuilder->attributes($payload, $request->user(), $gig));
        $gig = $mediaSync->sync($gig, $payload['media'] ?? [], $payload['galleryImages'] ?? $gig->gallery_images ?? []);
        event(new GigEdited($gig->fresh(['seller', 'media'])));

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig updated',
            "{$gig->title} changes were saved.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig->refresh()->load('media'));
    }

    public function updateStatus(
        UpdateSellerServiceStatusRequest $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        SellerGigLifecycleService $lifecycle
    ): SellerServiceResource {
        $this->authorizeSeller($request, $gig);

        $payload = $request->validated();

        if (in_array($payload['action'], ['activate', 'submit_review'], true)) {
            abort_unless($request->user()->seller_status === 'approved', 422, 'Your seller account must be approved before publishing gigs.');
        }

        $gig = match ($payload['action']) {
            'activate' => $lifecycle->activate($gig, $request->user()),
            'submit_review' => $lifecycle->submitForReview($gig, $request->user()),
            default => $lifecycle->pause($gig, $request->user()),
        };

        $notifier->notify(
            $request->user(),
            'Gig update',
            match ($payload['action']) {
                'activate' => 'Gig activated',
                'submit_review' => 'Gig submitted for review',
                default => 'Gig paused',
            },
            "{$gig->title} is now {$gig->status}.",
            "/dashboard/seller/services/{$gig->slug}/edit",
        );

        return SellerServiceResource::make($gig->load('media'));
    }

    public function destroy(
        Request $request,
        Gig $gig,
        MarketplaceNotifier $notifier,
        SellerGigLifecycleService $lifecycle
    ): Response {
        $this->authorizeSeller($request, $gig);

        $title = $gig->title;
        $lifecycle->delete($gig);

        $notifier->notify(
            $request->user(),
            'Gig update',
            'Gig deleted',
            "{$title} was removed from your seller services.",
            '/dashboard/seller/services',
        );

        return response()->noContent();
    }

    private function authorizeSeller(Request $request, Gig $gig): void
    {
        abort_unless($gig->seller_id === $request->user()->id, 403);
    }
}
