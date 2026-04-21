<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retrait extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'client_id',
        'carnet_id',
        'admin_id',
        'montant_total',
        'commission',
        'montant_net',
        'date_retrait',
        'note',
    ];

    protected $casts = [
        'date_retrait' => 'datetime',
        'montant_total' => 'decimal:2',
        'commission' => 'decimal:2',
        'montant_net' => 'decimal:2',
    ];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function carnet()
    {
        return $this->belongsTo(Carnet::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
