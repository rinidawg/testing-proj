<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubconAttachment extends Model
{
    protected $fillable = [
        'owner_type', 'owner_id', 'path', 'original_name', 'mime', 'size', 'uploaded_by',
    ];

    protected $casts = ['size' => 'integer'];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }
}
