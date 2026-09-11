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
 * @property int $order_id
 * @property string $code
 * @property string|null $secret
 * @property HotspotAccountStatus $status
 * @property Carbon|null $activated_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['order_id', 'code', 'secret', 'status', 'activated_at', 'expires_at'])]
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
