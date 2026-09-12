<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $price
 * @property int $duration_minutes
 * @property int|null $max_speed_mbps
 * @property int $max_devices
 * @property string|null $short_description
 * @property bool $is_popular
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name', 'price', 'duration_minutes', 'max_speed_mbps', 'max_devices',
    'short_description', 'is_popular', 'is_active', 'sort_order',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'max_speed_mbps' => 'integer',
            'max_devices' => 'integer',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Durée de validité en texte lisible ("1 heure", "3 jours", "1 mois"...).
     *
     * @return Attribute<string, never>
     */
    protected function durationLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $minutes = $this->duration_minutes;

            return match (true) {
                $minutes === 0 => self::pluralize(0, 'minute', 'minutes'),
                $minutes % 43200 === 0 => self::pluralize((int) ($minutes / 43200), 'mois', 'mois'),
                $minutes % 10080 === 0 => self::pluralize((int) ($minutes / 10080), 'semaine', 'semaines'),
                $minutes % 1440 === 0 => self::pluralize((int) ($minutes / 1440), 'jour', 'jours'),
                $minutes % 60 === 0 => self::pluralize((int) ($minutes / 60), 'heure', 'heures'),
                default => self::pluralize($minutes, 'minute', 'minutes'),
            };
        });
    }

    private static function pluralize(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count > 1 ? $plural : $singular);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Package>  $query
     * @return Builder<Package>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
