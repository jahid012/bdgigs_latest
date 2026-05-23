<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GigMedia extends Model
{
    use HasFactory;

    protected $table = 'gig_media';

    protected $fillable = [
        'gig_id',
        'type',
        'url',
        'thumbnail_url',
        'alt_text',
        'sort_order',
        'is_primary',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }
}
