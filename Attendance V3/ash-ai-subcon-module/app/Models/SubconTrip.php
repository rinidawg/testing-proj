<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubconTrip extends Model
{
    protected $fillable = [
        'subcontractor_id', 'subcon_po_id', 'kind', 'amount',
        'trip_date', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'trip_date' => 'date',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(SubconPo::class, 'subcon_po_id');
    }
}
