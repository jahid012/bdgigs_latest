<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_name',
        'account_number',
        'instructions',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ManualPaymentSubmission::class);
    }
}
