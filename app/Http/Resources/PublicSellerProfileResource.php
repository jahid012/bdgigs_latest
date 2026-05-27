<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        $featuredClients = collect($profile?->featured_clients ?: []);
        $project = $projects->first();
        $reviewedGigs = $gigs->filter(fn ($gig) => (int) $gig->reviews > 0);
        $reviews = (int) $reviewedGigs->sum('reviews');
        $rating = $reviews > 0
            ? round($reviewedGigs->sum(fn ($gig) => (float) $gig->rating * (int) $gig->reviews) / $reviews, 1)
            : 0.0;
        $online = $this->last_seen_at?->greaterThan(now()->subSeconds(90)) ?? false;

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
            'online' => $online,
            'availabilityStatus' => $online ? 'Online' : 'Offline',
            'lastSeenAt' => $this->last_seen_at?->toIso8601String(),
            'lastSeenLabel' => $this->lastSeenLabel($online),
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
            'portfolio' => $this->portfolioPayload($project),
            'portfolioProjects' => $projects->values(),
            'featuredClients' => $featuredClients->values(),
            'workExperience' => $this->workExperiencePayload($profile?->work_experience),
            'education' => $profile?->education,
            'certifications' => collect($profile?->certification ? [$profile->certification] : [])->values(),
            'reviewsData' => $this->reviewsData($reviewedGigs, $reviews, $rating),
        ];
    }

    private function portfolioPayload(mixed $project): ?array
    {
        if (! is_array($project) || empty(array_filter($project))) {
            return null;
        }

        $image = $project['image'] ?? '/assets/img/gig_images/1.png';
        $started = trim(($project['startedMonth'] ?? '').' '.($project['startedYear'] ?? ''));
        $thumbnails = is_array($project['thumbnails'] ?? null) ? $project['thumbnails'] : [];

        return [
            'title' => $project['name'] ?? $project['title'] ?? 'Portfolio project',
            'date' => $started ? "From: {$started}" : ($project['date'] ?? ''),
            'description' => $project['description'] ?? '',
            'image' => $image,
            'thumbnails' => array_values(array_filter([$image, ...$thumbnails])),
            'tags' => array_values(array_filter([$project['industry'] ?? null, $project['expertise'] ?? null])),
            'cost' => filled($project['cost'] ?? null) ? '$'.$project['cost'] : '',
            'duration' => $project['duration'] ?? '',
        ];
    }

    private function workExperiencePayload(mixed $workExperience): Collection
    {
        if (! $workExperience) {
            return collect();
        }

        $items = is_array($workExperience) && array_is_list($workExperience)
            ? $workExperience
            : [$workExperience];

        return collect($items)
            ->filter(fn ($work) => is_array($work) && ! empty(array_filter($work)))
            ->map(function (array $work) {
                $start = $work['startDate'] ?? null;
                $end = $work['endDate'] ?? null;

                return [
                    ...$work,
                    'role' => $work['role'] ?? $work['title'] ?? '',
                    'type' => $work['type'] ?? $work['employmentType'] ?? '',
                    'period' => $work['period'] ?? $this->workPeriod($start, $end),
                    'duration' => $work['duration'] ?? '',
                    'skills' => $this->skillList($work['skills'] ?? []),
                ];
            })
            ->values();
    }

    private function workPeriod(?string $start, ?string $end): string
    {
        $startLabel = $this->monthYear($start);
        $endLabel = $this->monthYear($end) ?: 'Present';

        return trim($startLabel.' - '.$endLabel, ' -');
    }

    private function monthYear(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function reviewsData(Collection $gigs, int $reviews, float $rating): array
    {
        $sampleGig = $gigs->sortByDesc('reviews')->first();

        return [
            'count' => $reviews,
            'rating' => $rating,
            'breakdown' => $reviews > 0 ? [
                ['label' => 'Seller communication', 'count' => $reviews, 'value' => min(100, max(0, (int) round($rating / 5 * 100)))],
                ['label' => 'Recommend to a friend', 'count' => max(0, $reviews - 1), 'value' => min(100, max(0, (int) round(($rating - 0.1) / 5 * 100)))],
                ['label' => 'Service as described', 'count' => $reviews, 'value' => min(100, max(0, (int) round(($rating + 0.1) / 5 * 100)))],
            ] : [],
            'ratings' => $reviews > 0 ? [
                ['label' => 'Seller communication', 'value' => min(5, $rating + 0.1)],
                ['label' => 'Recommend to a friend', 'value' => max(0, $rating - 0.1)],
                ['label' => 'Service as described', 'value' => min(5, $rating + 0.1)],
            ] : [],
            'sample' => $sampleGig ? $this->reviewSample($sampleGig) : null,
        ];
    }

    private function reviewSample($gig): array
    {
        $sample = is_array($gig->metadata) ? ($gig->metadata['reviewSample'] ?? []) : [];

        return [
            'name' => $sample['name'] ?? 'Verified buyer',
            'badge' => $sample['badge'] ?? 'Verified order',
            'country' => $sample['country'] ?? 'United States',
            'rating' => (float) ($sample['rating'] ?? $gig->rating),
            'date' => $sample['date'] ?? now()->subDays(12)->format('M d, Y'),
            'text' => $sample['text'] ?? 'Great communication, clear delivery, and exactly what the project needed.',
            'price' => $sample['price'] ?? '$'.number_format($gig->price_cents / 100, 0),
            'duration' => $sample['duration'] ?? $gig->delivery_days.' days',
            'serviceImage' => $gig->media->firstWhere('type', 'image')?->url ?: $gig->image,
            'serviceTitle' => $gig->category_label ?: $gig->title,
        ];
    }

    private function skillList(mixed $skills): array
    {
        if (is_string($skills)) {
            return collect(explode(',', $skills))->map(fn ($skill) => trim($skill))->filter()->values()->all();
        }

        return collect(is_array($skills) ? $skills : [])->map(fn ($skill) => trim((string) $skill))->filter()->values()->all();
    }

    private function lastSeenLabel(bool $online): string
    {
        if ($online) {
            return 'Online now';
        }

        return $this->last_seen_at ? 'Last seen '.$this->last_seen_at->diffForHumans() : 'Offline';
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
