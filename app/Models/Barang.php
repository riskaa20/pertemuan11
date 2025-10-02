<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $guarded = [];

    public function penjualanDetails()
    {
        return $this->hasMany(
            PenjualanDetail::class, 'barang_id', 'id'
        );
    }

    public function pembelians()
    {
        return $this->hasMany(
            Pembelian::class, 'barang_id', 'id'
        );
    }
}
