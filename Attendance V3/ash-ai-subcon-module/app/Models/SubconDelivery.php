<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubconDelivery extends Model
{
    protected $fillable = [
        'subcon_po_id', 'delivery_date', 'delivered_qty', 'accepted_qty',
        'reject_qty', 'repairs', 'scraps', 'received_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_qty' => 'integer',
        'accepted_qty'  => 'integer',
        'reject_qty'    => 'integer',
        'repairs'       => 'array',
        'scraps'        => 'array',
    ];

    public function po(): BelongsTo
    {
        return $this->belongsTo(SubconPo::class, 'subcon_po_id');
    }

    public function repairedQty(): int
    {
        return array_sum(array_column($this->repairs ?? [], 'qty'));
    }

    public function scrappedQty(): int
    {
        return array_sum(array_column($this->scraps ?? [], 'qty'));
    }

    /** Rejects not yet repaired or scrapped. */
    public function pendingRejects(): int
    {
        return (int) $this->reject_qty - $this->repairedQty() - $this->scrappedQty();
    }
}
