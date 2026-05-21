<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gig extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'slug',
        'title',
        'seller_name',
        'seller_avatar',
        'seller_level',
        'badge',
        'image',
        'category_id',
        'category_label',
        'price_cents',
        'rating',
        'reviews',
        'delivery_days',
        'seller_details',
        'service_options',
        'pro',
        'instant',
        'consultation',
        'featured',
        'search_text',
        'tag',
        'orders_label',
        'conversion_label',
        'status',
        'status_class',
        'packages',
        'extras',
        'requirements',
        'gallery_images',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'rating' => 'float',
            'reviews' => 'integer',
            'delivery_days' => 'integer',
            'seller_details' => 'array',
            'service_options' => 'array',
            'pro' => 'boolean',
            'instant' => 'boolean',
            'consultation' => 'boolean',
            'featured' => 'boolean',
            'packages' => 'array',
            'extras' => 'array',
            'requirements' => 'array',
            'gallery_images' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_services')->withTimestamps();
    }
}
