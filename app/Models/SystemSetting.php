<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getBool(string $key, bool $default = true): bool
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value === '1' : $default;
        });
    }

    public static function setBool(string $key, bool $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
        Cache::forget("setting.{$key}");
    }
}
