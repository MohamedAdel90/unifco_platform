<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class HomepageSection extends Model
{
    protected $fillable = [
        'section_key',
        'is_active',
        'sort_order',
        'data_ar',
        'data_en',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'data_ar' => 'array',
            'data_en' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public static function getByKey(string $key): ?self
    {
        return static::where('section_key', $key)->first();
    }

    public static function clearCache(): void
    {
        Cache::forget('homepage_content_ar');
        Cache::forget('homepage_content_en');
    }

    public function getData(string $locale): array
    {
        return $this->{"data_{$locale}"} ?? [];
    }
}
