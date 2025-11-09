<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'price',
        'image',
        'video',
        'address_info',
    ];
}
