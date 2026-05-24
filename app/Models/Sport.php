<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $fillable = ['name', 'icon'];

    public function leagues()
    {
        return $this->hasMany(League::class);
    }
}
