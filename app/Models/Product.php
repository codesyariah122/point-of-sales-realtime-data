<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'products';

    protected $fillable = [
        'barcode',
        'name'
    ];

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category')->withTrashed();
    }

    public function orders()
    {
        return $this->belongsToMany('App\Models\Order');
    }
}
