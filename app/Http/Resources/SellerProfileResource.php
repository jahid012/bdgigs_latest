<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->sellerProfile;

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
            'workExperience' => $profile?->work_experience,
            'education' => $profile?->education,
            'certification' => $profile?->certification,
            'rating' => number_format((float) $this->gigs()->avg('rating'), 1),
            'reviews' => (string) $this->gigs()->sum('reviews'),
        ];
    }
}
