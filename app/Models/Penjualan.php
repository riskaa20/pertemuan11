<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $guarded = [];

    public function pelanggan()
    {
        return $this->belongsTo(
            Pelanggan::class, 'pelanggan_id', 'id'
        );
    }

    public function penjualanDetails()
    {
        return $this->hasMany(
            PenjualanDetail::class, 'penjualan_id', 'id'
        );
    }

}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               

