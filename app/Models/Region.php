<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = ['name'];

    public function countries()
    {
        return $this->hasMany(Country::class);
    }
}
