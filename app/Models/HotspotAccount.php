<?php

namespace App\Models;

use App\Enums\HotspotAccountStatus;
use Database\Factories\HotspotAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $order_id
 * @property int|null $package_id
 * @property string $code
 * @property string|null $secret
 * @property HotspotAccountStatus $status
 * @property Carbon|null $activated_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['order_id', 'package_id', 'code', 'secret', 'status', 'activated_at', 'expires_at'])]
#[Hidden(['secret'])]
class HotspotAccount extends Model
{
    /** @use HasFactory<HotspotAccountFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => HotspotAccountStatus::class,
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Null pour un voucher généré directement par l'admin (voir
     * CreateHotspotAccountAction) : ce compte n'est rattaché à aucun achat.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Toujours renseigné à la création (par une commande ou par un voucher
     * admin) : contrairement à `order.package`, cette relation fonctionne
     * quelle que soit l'origine du compte.
     *
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Un compte sans commande a été généré comme voucher directement par
     * l'admin (voir CreateHotspotAccountAction), pas acheté par un client.
     */
    public function isVoucher(): bool
    {
        return $this->order_id === null;
    }

    /**
     * @param  Builder<HotspotAccount>  $query
     * @return Builder<HotspotAccount>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', HotspotAccountStatus::Active);
    }
}
