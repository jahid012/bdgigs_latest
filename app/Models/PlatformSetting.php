<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'meta' => 'array',
        ];
    }
}
