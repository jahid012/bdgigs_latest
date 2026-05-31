<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadAvatarRequest;
use App\Http\Requests\Api\UpdateBuyerProfileRequest;
use App\Http\Requests\Api\UpdateSellerProfileRequest;
use App\Http\Resources\AvatarResource;
use App\Http\Resources\BuyerProfileResource;
use App\Http\Resources\DashboardSummaryResource;
use App\Http\Resources\PublicSellerProfileResource;
use App\Http\Resources\SellerProfileResource;
use App\Models\User;
use App\Services\CountryDetectorService;
use App\Services\DashboardSummaryService;
use App\Services\ProfileAvatarUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function dashboard(Request $request, DashboardSummaryService $summary): DashboardSummaryResource
    {
        $variant = $request->query('variant') === 'seller' ? 'seller' : 'buyer';

        return DashboardSummaryResource::make($summary->for($request->user(), $variant));
    }

    public function buyerProfile(Request $request, CountryDetectorService $countries): BuyerProfileResource
    {
        $this->fillCountryFromRequest($request, $countries);

        return BuyerProfileResource::make($request->user()->loadMissing('buyerProfile'));
    }

    public function updateBuyerProfile(UpdateBuyerProfileRequest $request, CountryDetectorService $countries): BuyerProfileResource
    {
        $payload = $request->validated();
        $this->fillCountryFromRequest($request, $countries);

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

    public function sellerProfile(Request $request, CountryDetectorService $countries): SellerProfileResource
    {
        $this->fillCountryFromRequest($request, $countries);

        return SellerProfileResource::make($request->user()->loadMissing('sellerProfile'));
    }

    public function updateSellerProfile(UpdateSellerProfileRequest $request, CountryDetectorService $countries): SellerProfileResource
    {
        $payload = $request->validated();
        $this->fillCountryFromRequest($request, $countries);

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
            'featured_clients' => $payload['featuredClients'] ?? $user->sellerProfile?->featured_clients,
            'work_experience' => $payload['workExperience'] ?? $user->sellerProfile?->work_experience,
            'education' => $payload['education'] ?? $user->sellerProfile?->education,
            'certification' => $payload['certification'] ?? $user->sellerProfile?->certification,
        ]);

        return SellerProfileResource::make($user->fresh('sellerProfile'));
    }

    public function avatar(UploadAvatarRequest $request, ProfileAvatarUploadService $uploads): AvatarResource
    {
        $avatar = $uploads->store($request->validated('avatar'), $request->user()->id);

        $request->user()->forceFill(['avatar' => $avatar])->save();

        return AvatarResource::make($avatar);
    }

    public function publicSellerProfile(string $username): PublicSellerProfileResource
    {
        $user = User::query()
            ->where('username', ltrim($username, '@'))
            ->first()
            ?: User::all()->first(fn (User $candidate) => Str::slug($candidate->name) === $username);

        abort_unless($user, 404);
        abort_unless($user->sellerProfile || $user->gigs()->exists(), 404);

        return PublicSellerProfileResource::make($user->loadMissing(['sellerProfile', 'gigs.media']));
    }

    private function fillCountryFromRequest(Request $request, CountryDetectorService $countries): void
    {
        if ($request->user()->country) {
            return;
        }

        $country = $countries->detect($request);

        if ($country) {
            $request->user()->forceFill(['country' => $country])->save();
        }
    }
}
