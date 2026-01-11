<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'mime_type',
        'size',
        'mediable_type',
        'mediable_id',
        'collection',
        'original_filename',
        'status',
    ];

    public function mediable()
    {
        return $this->morphTo();
    }
}
