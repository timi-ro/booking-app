<?php

namespace App\Offering\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferingDay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'offering_id',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }
}
