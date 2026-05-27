<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'link_url',
        'sort_order',
        'active',
        'show_in_mega_menu',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'show_in_mega_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeMegaMenu(Builder $query): Builder
    {
        return $query->where('show_in_mega_menu', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function path(): string
    {
        if ($this->link_url) {
            return $this->link_url;
        }

        if ($this->parent) {
            return '/categories/'.$this->parent->slug.'/'.$this->slug;
        }

        $firstChild = $this->relationLoaded('children') ? $this->children->first() : null;

        return $firstChild
            ? '/categories/'.$this->slug.'/'.$firstChild->slug
            : '/search/gigs?query='.urlencode($this->name).'&source=category-nav';
    }
}
