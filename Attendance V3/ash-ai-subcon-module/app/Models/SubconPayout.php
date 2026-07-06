<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubconPayout extends Model
{
    protected $fillable = [
        'subcontractor_id', 'week_start', 'amount', 'status', 'paid_at', 'paid_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'amount'     => 'decimal:2',
        'paid_at'    => 'date',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }
}
