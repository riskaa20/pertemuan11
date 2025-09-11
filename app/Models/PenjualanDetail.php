<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $guarded = [];

    public function penjualans()
    {
        return $this->belongTo(
            Penjualan::class, 'penjualan_id', 'id'
        );
    }

    public function barangs()
    {
        return $this->belongsTo(
            Barang::class, 'barang_id', 'id'
        );
    }
}
