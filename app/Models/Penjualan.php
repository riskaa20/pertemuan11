<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $guarded = [];

    public function pelanggans()
    {
        return $this->belongsTo(
            Pelanggan::class, 'pelanggan_id', 'id'
        );
    }

    public function penjualanDetails()
    {
        return $this->hasMany(
            PenjualanDetails::class, 'penjualan_id', 'id'
        );
    }

}
