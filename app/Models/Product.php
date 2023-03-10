<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;
    
    protected $table = 'products';

    protected $fillable = [
        'barcode',
        'name'
    ];

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category');
    }

    public function orders()
    {
        return $this->belongsToMany('App\Models\Order');
    }
}
