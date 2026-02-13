<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    protected $fillable = ['pista_id', 'fecha_hora', 'user_id'];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function pista(): BelongsTo
    {
        return $this->belongsTo(Pista::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
