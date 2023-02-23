<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrandTotalOrder extends Model
{
    use HasFactory;

    protected $table = 'grand_total_orders';

    public function orders()
    {
        return $this->belongsToMany('App\Models\Order');
    }
}
