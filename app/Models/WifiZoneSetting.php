<?php

namespace App\Models;

use App\Enums\CredentialMode;
use Database\Factories\WifiZoneSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slogan
 * @property string|null $description
 * @property string|null $logo_path
 * @property string $color_primary
 * @property string $color_secondary
 * @property string $color_accent
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $address
 * @property string|null $opening_hours
 * @property array<string, string>|null $social_links
 * @property string|null $terms
 * @property string|null $privacy_policy
 * @property CredentialMode $credential_mode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slogan', 'description', 'logo_path',
    'color_primary', 'color_secondary', 'color_accent',
    'phone', 'whatsapp', 'email', 'address', 'opening_hours',
    'social_links', 'terms', 'privacy_policy', 'credential_mode',
])]
class WifiZoneSetting extends Model
{
    /** @use HasFactory<WifiZoneSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'credential_mode' => CredentialMode::class,
        ];
    }

    /**
     * Get the single active zone configuration, creating a default one if none exists yet.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }
}
