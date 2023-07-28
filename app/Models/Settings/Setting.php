<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key): mixed
    {
        return self::query()->where('key', $key)->first()->value ?? null;
    }

    public static function saveValue(string $key, mixed $value): mixed
    {
        $setting = self::query()->where('key', $key)->first();

        if ($setting) {
            $setting->value = $value;
            $setting->save();

            return $setting->fresh();
        }

        return self::query()->create(['key' => $key, 'value' => $value])->value ?? null;
    }
}
