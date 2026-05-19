<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlatformSettings
{
    public static function groups(): array
    {
        return config('platform_settings.groups', []);
    }

    public static function flatDefinitions(): array
    {
        return collect(self::groups())
            ->flatMap(function (array $group) {
                return collect($group['settings'] ?? [])->mapWithKeys(function (array $setting) use ($group) {
                    $setting['group_key'] = $group['key'] ?? 'general';

                    return [$setting['name'] => $setting];
                });
            })
            ->all();
    }

    public static function definitionsWithValues(): array
    {
        self::syncDefinitions();
        $values = self::all();

        return collect(self::groups())
            ->map(function (array $group) use ($values) {
                $group['settings'] = collect($group['settings'] ?? [])
                    ->map(function (array $setting) use ($values) {
                        $setting['value'] = $values[$setting['name']] ?? $setting['value'] ?? null;

                        return $setting;
                    })
                    ->values()
                    ->all();

                return $group;
            })
            ->values()
            ->all();
    }

    public static function all(): array
    {
        $cacheKey = self::cacheKey();

        return Cache::rememberForever($cacheKey, function () {
            $definitions = self::flatDefinitions();
            $values = collect($definitions)
                ->mapWithKeys(fn (array $definition, string $key) => [
                    $key => self::castValue($definition['value'] ?? null, $definition['type'] ?? 'text'),
                ])
                ->all();

            if (! Schema::hasTable('platform_settings')) {
                return $values;
            }

            PlatformSetting::query()
                ->get()
                ->each(function (PlatformSetting $setting) use (&$values, $definitions) {
                    $type = $setting->type ?: ($definitions[$setting->setting_key]['type'] ?? 'text');
                    $values[$setting->setting_key] = self::castValue($setting->value, $type);
                });

            return $values;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Arr::get(self::all(), $key, $default);
    }

    public static function group(string $groupKey): array
    {
        return collect(self::definitionsWithValues())
            ->firstWhere('key', $groupKey) ?? [];
    }

    public static function set(string $key, mixed $value): void
    {
        $definition = self::flatDefinitions()[$key] ?? [
            'name' => $key,
            'type' => 'text',
            'label' => str($key)->replace('_', ' ')->title()->toString(),
            'description' => null,
            'group_key' => 'custom',
            'options' => null,
        ];

        PlatformSetting::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'group_key' => $definition['group_key'] ?? 'custom',
                'type' => $definition['type'] ?? 'text',
                'label' => $definition['label'] ?? null,
                'description' => $definition['description'] ?? null,
                'value' => self::serializeValue($value, $definition['type'] ?? 'text'),
                'options' => $definition['options'] ?? null,
                'meta' => Arr::only($definition, ['prefix', 'suffix']),
            ]
        );

        self::clearCache();
    }

    public static function setMany(array $values): void
    {
        foreach (self::flatDefinitions() as $key => $definition) {
            $type = $definition['type'] ?? 'text';
            $value = $type === 'toggle'
                ? array_key_exists($key, $values)
                : ($values[$key] ?? $definition['value'] ?? null);

            self::set($key, $value);
        }
    }

    public static function syncDefinitions(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        foreach (self::flatDefinitions() as $key => $definition) {
            $setting = PlatformSetting::query()->firstOrNew(['setting_key' => $key]);

            if (! $setting->exists) {
                $setting->value = self::serializeValue($definition['value'] ?? null, $definition['type'] ?? 'text');
            }

            $setting->fill([
                'group_key' => $definition['group_key'] ?? 'general',
                'type' => $definition['type'] ?? 'text',
                'label' => $definition['label'] ?? null,
                'description' => $definition['description'] ?? null,
                'options' => $definition['options'] ?? null,
                'meta' => Arr::only($definition, ['prefix', 'suffix']),
            ]);

            $setting->save();
        }

        self::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    public static function validateInput(array $values): array
    {
        $errors = [];

        foreach (self::flatDefinitions() as $key => $definition) {
            $type = $definition['type'] ?? 'text';
            $value = $values[$key] ?? null;

            if ($type === 'number' && $value !== null && $value !== '' && ! is_numeric($value)) {
                $errors[$key] = ($definition['label'] ?? $key).' must be a valid number.';
            }

            if ($type === 'select' && $value !== null && ! in_array($value, $definition['options'] ?? [], true)) {
                $errors[$key] = ($definition['label'] ?? $key).' contains an invalid option.';
            }
        }

        return $errors;
    }

    private static function cacheKey(): string
    {
        return config('platform_settings.cache_key', 'platform_settings.values');
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? $value + 0 : 0,
            default => $value,
        };
    }

    private static function serializeValue(mixed $value, string $type): ?string
    {
        return match ($type) {
            'toggle' => $value ? '1' : '0',
            'number' => is_numeric($value) ? (string) ($value + 0) : '0',
            default => $value === null ? null : (string) $value,
        };
    }
}
