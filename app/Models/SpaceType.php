<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceType extends Model
{
    protected $fillable = ['name', 'description'];

    public function spaces()
    {
        return $this->hasMany(Space::class);
    }
}
