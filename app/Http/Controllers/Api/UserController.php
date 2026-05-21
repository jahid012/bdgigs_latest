<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateBuyerProfileRequest;
use App\Http\Requests\Api\UpdateSellerProfileRequest;
use App\Http\Resources\BuyerProfileResource;
use App\Http\Resources\PublicSellerProfileResource;
use App\Http\Resources\SellerProfileResource;
use App\Models\User;
use App\Services\DashboardSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard(Request $request, DashboardSummaryService $summary): JsonResponse
    {
        $variant = $request->query('variant') === 'seller' ? 'seller' : 'buyer';

        return response()->json([
            'data' => $summary->for($request->user(), $variant),
        ]);
    }

    public function buyerProfile(Request $request): BuyerProfileResource
    {
        return BuyerProfileResource::make($request->user()->loadMissing('buyerProfile'));
    }

    public function updateBuyerProfile(UpdateBuyerProfileRequest $request): BuyerProfileResource
    {
        $payload = $request->validated();

        $user = $request->user();
        $user->forceFill(array_filter([
            'name' => $payload['name'] ?? null,
            'avatar' => array_key_exists('avatar', $payload) ? $payload['avatar'] : null,
            'country' => array_key_exists('country', $payload) ? $payload['country'] : null,
        ], fn ($value) => $value !== null))->save();
        $user->buyerProfile()->updateOrCreate([], [
            'overview' => $payload['overview'] ?? $user->buyerProfile?->overview,
            'working_days' => $payload['workingDays'] ?? $user->buyerProfile?->working_days,
            'working_hours' => $payload['workingHours'] ?? $user->buyerProfile?->working_hours,
            'timezone' => $payload['timezone'] ?? $user->buyerProfile?->timezone,
            'languages' => $payload['languages'] ?? $user->buyerProfile?->languages,
        ]);

        return BuyerProfileResource::make($user->fresh('buyerProfile'));
    }

    public function sellerProfile(Request $request): SellerProfileResource
    {
        return SellerProfileResource::make($request->user()->loadMissing('sellerProfile'));
    }

    public function updateSellerProfile(UpdateSellerProfileRequest $request): SellerProfileResource
    {
        $payload = $request->validated();

        $user = $request->user();
        $user->forceFill(array_filter([
            'name' => $payload['name'] ?? null,
            'avatar' => array_key_exists('avatar', $payload) ? $payload['avatar'] : null,
            'country' => array_key_exists('country', $payload) ? $payload['country'] : null,
        ], fn ($value) => $value !== null))->save();
        $user->sellerProfile()->updateOrCreate([], [
            'professional_title' => $payload['title'] ?? $user->sellerProfile?->professional_title,
            'about' => $payload['about'] ?? $user->sellerProfile?->about,
            'languages' => $payload['languages'] ?? $user->sellerProfile?->languages,
            'skills' => $payload['skills'] ?? $user->sellerProfile?->skills,
            'portfolio_projects' => $payload['projects'] ?? $user->sellerProfile?->portfolio_projects,
            'work_experience' => $payload['workExperience'] ?? $user->sellerProfile?->work_experience,
            'education' => $payload['education'] ?? $user->sellerProfile?->education,
            'certification' => $payload['certification'] ?? $user->sellerProfile?->certification,
        ]);

        return SellerProfileResource::make($user->fresh('sellerProfile'));
    }

    public function avatar(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'avatar' => ['required', 'string', 'max:1500000'],
        ]);

        $request->user()->forceFill(['avatar' => $payload['avatar']])->save();

        return response()->json([
            'data' => [
                'avatar' => $request->user()->avatar,
            ],
        ]);
    }

    public function publicSellerProfile(User $user): PublicSellerProfileResource
    {
        abort_unless($user->sellerProfile || $user->gigs()->exists(), 404);

        return PublicSellerProfileResource::make($user->loadMissing(['sellerProfile', 'gigs']));
    }
}
