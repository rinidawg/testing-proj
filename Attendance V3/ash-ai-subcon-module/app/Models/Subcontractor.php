<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcontractor extends Model
{
    protected $fillable = ['name', 'contact', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function pos(): HasMany
    {
        return $this->hasMany(SubconPo::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(SubconTrip::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(SubconPayout::class);
    }
}
