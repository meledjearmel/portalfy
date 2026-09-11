<?php

namespace Database\Seeders;

use App\Enums\CredentialMode;
use App\Models\WifiZoneSetting;
use Illuminate\Database\Seeder;

class WifiZoneSettingSeeder extends Seeder
{
    /**
     * Configuration de démonstration de la WiFi Zone.
     */
    public function run(): void
    {
        WifiZoneSetting::query()->updateOrCreate([], [
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
        ]);
    }
}
