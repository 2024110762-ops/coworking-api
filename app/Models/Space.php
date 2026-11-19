<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    protected $fillable = ['space_type_id', 'name', 'floor', 'capacity', 'price_per_hour', 'is_active'];

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Scope para espacios activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}