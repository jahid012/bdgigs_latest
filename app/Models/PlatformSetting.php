<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'group_key',
        'type',
        'label',
        'description',
        'value',
        'options',
        'meta',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'meta' => 'array',
        ];
    }

    public function adminUpdater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
