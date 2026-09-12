<?php

namespace App\Models;

use App\Enums\CredentialMode;
use Database\Factories\WifiZoneSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slogan
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $background_path
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
 * @property-read string $background_url
 * @property-read string $logo_url
 */
#[Fillable([
    'name', 'slogan', 'description', 'logo_path', 'background_path',
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
     *
     * firstOrCreate() ne recharge pas les valeurs par défaut posées par la
     * migration (name, color_primary, credential_mode...) : juste après
     * création, l'instance en mémoire n'a que ['id' => 1] et ces colonnes y
     * apparaissent à tort comme null tant qu'on ne la recharge pas depuis la BDD.
     */
    public static function current(): self
    {
        $zone = self::query()->firstOrCreate(['id' => 1]);

        return $zone->wasRecentlyCreated ? ($zone->fresh() ?? $zone) : $zone;
    }

    /**
     * URL du fond de page : celui choisi par l'admin dans les paramètres, ou
     * l'image par défaut du thème si aucun n'a été défini. Disque "public"
     * explicite : le disque par défaut de l'app est "local" (privé,
     * storage/app/private), un Storage::url() sans disque explicite produit
     * une URL cassée pour un fichier qui n'y vit pas réellement.
     *
     * @return Attribute<string, never>
     */
    protected function backgroundUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->background_path
            ? Storage::disk('public')->url($this->background_path)
            : asset('images/hero-background.jpg'));
    }

    /**
     * URL du logo, ou chaîne vide si aucun n'a été défini par l'admin.
     *
     * @return Attribute<string, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : '');
    }
}
