<?php

namespace App\Offering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Availability extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'offering_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }
}
