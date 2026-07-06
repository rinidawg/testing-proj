<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubconPo extends Model
{
    protected $table = 'subcon_pos';

    protected $fillable = [
        'code', 'subcontractor_id', 'style', 'qty', 'rate',
        'po_date', 'due_date', 'status', 'created_by',
    ];

    protected $casts = [
        'qty'      => 'integer',
        'rate'     => 'decimal:2',
        'po_date'  => 'date',
        'due_date' => 'date',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SubconDelivery::class);
    }

    public function deliveredQty(): int
    {
        return (int) $this->deliveries()->sum('delivered_qty');
    }
}
