<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'professional_title',
        'about',
        'languages',
        'skills',
        'portfolio_projects',
        'featured_clients',
        'work_experience',
        'education',
        'certification',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'skills' => 'array',
            'portfolio_projects' => 'array',
            'featured_clients' => 'array',
            'work_experience' => 'array',
            'education' => 'array',
            'certification' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
