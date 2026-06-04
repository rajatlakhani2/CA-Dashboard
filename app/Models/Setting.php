<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['key', 'value', 'organization_id'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
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
