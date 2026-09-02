<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class HomepageProject extends Model
{
    protected $fillable = [
        'sort_order',
        'is_active',
        'year',
        'image',
        'title_ar',
        'title_en',
        'owner_ar',
        'owner_en',
        'location_ar',
        'location_en',
        'scope_ar',
        'scope_en',
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
            'image' => $this->image,
            'year' => $this->year,
            'title' => $this->{"title_{$locale}"},
            'owner' => $this->{"owner_{$locale}"},
            'location' => $this->{"location_{$locale}"},
            'scope' => $this->{"scope_{$locale}"},
        ];
    }
}
