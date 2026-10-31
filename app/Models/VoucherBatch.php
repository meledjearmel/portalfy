<?php

namespace App\Models;

use Database\Factories\VoucherBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Codes créés ensemble par l'admin (ex. 50 codes d'une heure), imprimables
 * ensemble à tout moment.
 *
 * @property int $id
 * @property int $package_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['package_id', 'quantity'])]
class VoucherBatch extends Model
{
    /** @use HasFactory<VoucherBatchFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    /**
     * @return HasMany<HotspotAccount, $this>
     */
    public function hotspotAccounts(): HasMany
    {
        return $this->hasMany(HotspotAccount::class);
    }
}
