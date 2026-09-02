<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class HomepageClient extends Model
{
    protected $fillable = [
        'sort_order',
        'is_active',
        'image',
        'name_ar',
        'name_en',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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

    public static function clearCache(): void
    {
        Cache::forget('homepage_content_ar');
        Cache::forget('homepage_content_en');
    }

    public function toArrayForLocale(string $locale): array
    {
        return [
            $this->image,
            $this->{"name_{$locale}"},
        ];
    }
}
