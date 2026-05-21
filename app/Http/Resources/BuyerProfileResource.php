<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->buyerProfile;

        return [
            'name' => $this->name,
            'handle' => '@'.$this->username,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'location' => $this->country ?: '',
            'joined' => $this->created_at ? 'Joined in '.$this->created_at->format('F Y') : '',
            'overview' => $profile?->overview ?: '',
            'workingDays' => $profile?->working_days ?: ['start' => '', 'end' => ''],
            'workingHours' => $profile?->working_hours ?: ['start' => '', 'end' => ''],
            'timezone' => $profile?->timezone ?: config('app.timezone'),
            'languages' => $profile?->languages ?: [],
        ];
    }
}
