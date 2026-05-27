<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->sellerProfile;
        $gigs = $this->gigs()->select(['rating', 'reviews'])->get();
        $reviews = (int) $gigs->sum('reviews');
        $rating = $reviews > 0
            ? round($gigs->sum(fn ($gig) => (float) $gig->rating * (int) $gig->reviews) / $reviews, 1)
            : 0;

        return [
            'name' => $this->name,
            'handle' => '@'.$this->username,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'location' => $this->country ?: '',
            'title' => $profile?->professional_title ?: '',
            'about' => $profile?->about ?: '',
            'languages' => $profile?->languages ?: [],
            'skills' => $profile?->skills ?: [],
            'projects' => $profile?->portfolio_projects ?: [],
            'featuredClients' => $profile?->featured_clients ?: [],
            'workExperience' => $profile?->work_experience,
            'education' => $profile?->education,
            'certification' => $profile?->certification,
            'rating' => number_format($rating, 1),
            'reviews' => (string) $reviews,
        ];
    }
}
