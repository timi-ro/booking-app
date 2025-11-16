<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'mime_type',
        'size',
        'mediable_type',
        'mediable_id',
        'collection',
    ];

    public function mediable()
    {
        return $this->morphTo();
    }
}
