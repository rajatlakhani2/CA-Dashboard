<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['key', 'value', 'organization_id'];

    /**
     * Read a setting. All settings of the current firm are loaded with one query and kept for the
     * rest of the request (pages read dozens of settings; one query each made every page slow).
     * Lookup order and results are the same as before: the firm's own value, then the fallback query.
     */
    public static function get($key, $default = null)
    {
        $orgId = \App\Support\OrganizationContext::id();
        $memo = self::memo();

        if ($orgId) {
            $bucket = 'org:'.$orgId;
            if (! isset($memo[$bucket])) {
                $memo[$bucket] = self::where('organization_id', $orgId)->orderByDesc('id')->pluck('value', 'key')->all();
            }
            if (array_key_exists($key, $memo[$bucket])) {
                return $memo[$bucket][$key];
            }
        }

        $bucket = 'null:'.($orgId ?: 'none');
        if (! isset($memo[$bucket])) {
            $memo[$bucket] = self::whereNull('organization_id')->orderByDesc('id')->pluck('value', 'key')->all();
        }

        return array_key_exists($key, $memo[$bucket]) ? $memo[$bucket][$key] : $default;
    }

    /** Per-request store (lives in the container, so every request and every test starts empty). */
    private static function memo(): \ArrayObject
    {
        $app = app();
        if (! $app->bound('settings.memo')) {
            $app->instance('settings.memo', new \ArrayObject());
        }

        return $app->make('settings.memo');
    }

    public static function flushCache(): void
    {
        if (app()->bound('settings.memo')) {
            app()->forgetInstance('settings.memo');
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    public static function set($key, $value)
    {
        $attrs = ['key' => $key];
        if (\App\Support\OrganizationContext::id()) {
            $attrs['organization_id'] = \App\Support\OrganizationContext::id();
        }

        return self::updateOrCreate($attrs, ['value' => $value]);
    }
}
