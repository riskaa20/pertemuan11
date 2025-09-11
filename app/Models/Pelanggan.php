<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $guarded = [];

    public function penjualans()
        {
            return $this->hasMany(
                Penjualan::class, 'pelanggan_id', 'id'
            );
        }
}
