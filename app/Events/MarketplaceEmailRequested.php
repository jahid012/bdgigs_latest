<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceEmailRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $templateKey,
        public array $data = [],
        public array $options = [],
    ) {
    }
}
