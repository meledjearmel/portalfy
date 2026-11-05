<?php

namespace Database\Seeders;

use App\Enums\CredentialMode;
use App\Models\WifiZoneSetting;
use Illuminate\Database\Seeder;

class WifiZoneSettingSeeder extends Seeder
{
    /**
     * Paramètres de la WiFi Zone : démo en local, ligne vierge ailleurs
     * (créée seulement si absente, la config admin n'est jamais écrasée).
     */
    public function run(): void
    {
        $attributes = [
            'name' => 'RapidNet Wifi Zone',
            'slogan' => 'Internet premium, partout, sans compte obligatoire',
            'description' => 'Le hotspot WiFi payant qui vous connecte en quelques secondes.',
            'color_primary' => '#0F9D8C',
            'color_secondary' => '#0B2B26',
            'color_accent' => '#0F9D8C',
            'phone' => '+225 07 00 00 00 00',
            'whatsapp' => '+225 07 00 00 00 00',
            'email' => 'contact@rapidnet.test',
            'address' => 'Abidjan, Côte d\'Ivoire',
            'opening_hours' => '24h/24, 7j/7',
            'social_links' => [],
            'terms' => 'Conditions générales d\'utilisation de démonstration.',
            'privacy_policy' => 'Politique de confidentialité de démonstration.',
            'credential_mode' => CredentialMode::Unique,
        ];

        if (app()->environment('local')) {
            WifiZoneSetting::query()->updateOrCreate(['id' => 1], $attributes);

            return;
        }

        WifiZoneSetting::query()->firstOrCreate(['id' => 1]);
    }
}
