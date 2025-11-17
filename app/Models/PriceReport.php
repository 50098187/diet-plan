<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceReport extends Model
{
    protected $fillable = [
        'food_id',
        'user_id',
        'reported_price',
        'store_location',
        'store_chain',
        'notes',
        'verified',
        'reported_at',
    ];

    protected $casts = [
        'reported_price' => 'decimal:2',
        'verified' => 'boolean',
        'reported_at' => 'datetime',
    ];

    /**
     * Get the food this report is for
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * Get the user who reported this price
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for verified reports only
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for recent reports (within last 7 days)
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('reported_at', '>=', now()->subDays($days));
    }
}
