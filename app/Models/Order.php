<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $table = 'orders';

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function customers()
    {
        return $this->belongsToMany('App\Models\Customer');
    }

    public function products()
    {
        return $this->belongsToMany('App\Models\Product');
    }

    public function grand_total_orders()
    {
        return $this->belongsToMany('App\Models\GrandTotalOrder');
    }
}
