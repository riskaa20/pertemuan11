<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $guarded = [];

    public function penjualans()
    {
        return $this->belongsTo(
            Penjualan::class, 'penjualan_id', 'id'
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            Barang::class, 'barang_id', 'id'
        );
    }
}
