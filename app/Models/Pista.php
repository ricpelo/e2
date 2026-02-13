<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pista extends Model
{
    protected $fillable = ['nombre'];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}
