<?php

namespace App\Models;

use Database\Factories\RouterSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $host
 * @property int $port
 * @property string|null $username
 * @property string|null $password
 * @property int $timeout
 * @property bool $use_ssl
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['host', 'port', 'username', 'password', 'timeout', 'use_ssl'])]
#[Hidden(['password'])]
class RouterSetting extends Model
{
    /** @use HasFactory<RouterSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'timeout' => 'integer',
            'use_ssl' => 'boolean',
        ];
    }

    /**
     * Get the single router configuration row, created empty on first access.
     *
     * Aucune valeur n'est plus jamais lue depuis .env ici : tant que l'admin
     * n'a pas explicitement enregistré une configuration depuis l'écran
     * Routeur, host/username restent vides et isConfigured() renvoie false.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * Un routeur n'est considéré comme configuré que si l'admin a
     * explicitement renseigné une adresse et un utilisateur — le reste
     * (port, délai, SSL) a des valeurs par défaut raisonnables mais ne
     * suffit pas à lui seul à tenter une connexion utile.
     */
    public function isConfigured(): bool
    {
        return filled($this->host) && filled($this->username);
    }

    /**
     * Traduit la configuration enregistrée vers le format de tableau attendu
     * par `MikrotikManager` (clé `ssl`, pas `use_ssl`, et mot de passe en
     * chaîne vide plutôt que null pour la connexion RouterOS).
     *
     * @return array{host: string, port: int, username: string, password: string, timeout: int, ssl: bool}
     */
    public function toMikrotikConfig(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password ?? '',
            'timeout' => $this->timeout,
            'ssl' => $this->use_ssl,
        ];
    }
}
