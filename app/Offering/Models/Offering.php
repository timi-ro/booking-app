<?php

namespace App\Offering\Models;

use App\Auth\Models\User;
use App\Media\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offering extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'address_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function offeringDays()
    {
        return $this->hasMany(OfferingDay::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }
}
