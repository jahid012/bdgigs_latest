<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSellerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->sellerProfile;
        $gigs = $this->whenLoaded('gigs', $this->gigs);
        $languages = collect($profile?->languages ?: [])
            ->map(fn ($language) => is_array($language) ? ($language['language'] ?? null) : $language)
            ->filter()
            ->values();
        $projects = collect($profile?->portfolio_projects ?: []);
        $project = $projects->first();
        $reviews = (int) $gigs->sum('reviews');
        $rating = (float) ($gigs->avg('rating') ?: 0);

        return [
            'userId' => $this->id,
            'slug' => $this->username,
            'name' => $this->name,
            'handle' => '@'.$this->username,
            'avatar' => $this->avatar,
            'initials' => publicSellerInitials($this->name),
            'title' => $profile?->professional_title ?: '',
            'level' => $gigs->first()?->seller_level ?: 'New Seller',
            'rating' => $rating,
            'reviews' => $reviews,
            'location' => $this->country ?: '',
            'localTime' => now()->format('g:i A'),
            'languages' => $languages,
            'about' => $profile?->about ?: '',
            'skills' => $profile?->skills ?: [],
            'responseTime' => 'Inbox',
            'services' => $gigs->map(fn ($gig) => [
                'id' => $gig->slug,
                'title' => $gig->category_label ?: $gig->title,
                'description' => $gig->title,
                'image' => $gig->media->firstWhere('type', 'image')?->url ?: $gig->image,
                'price' => $gig->price_cents / 100,
            ])->values(),
            'portfolio' => $project ? [
                'title' => $project['name'] ?? '',
                'date' => trim('From: '.($project['startedMonth'] ?? '').' '.($project['startedYear'] ?? '')),
                'description' => $project['description'] ?? '',
                'image' => $project['image'] ?? null,
                'thumbnails' => array_values(array_filter([$project['image'] ?? null])),
                'tags' => array_values(array_filter([$project['industry'] ?? null, $project['expertise'] ?? null])),
                'cost' => isset($project['cost']) ? '$'.$project['cost'] : '',
                'duration' => $project['duration'] ?? '',
            ] : null,
            'portfolioProjects' => $projects->values(),
            'workExperience' => collect($profile?->work_experience ? [$profile->work_experience] : [])->values(),
            'education' => $profile?->education,
            'certifications' => collect($profile?->certification ? [$profile->certification] : [])->values(),
            'reviewsData' => [
                'count' => $reviews,
                'rating' => $rating,
                'breakdown' => [],
                'ratings' => [],
                'sample' => null,
            ],
        ];
    }
}

function publicSellerInitials(string $name): string
{
    return collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
}
