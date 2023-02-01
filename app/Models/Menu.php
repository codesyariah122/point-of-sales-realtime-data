<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $casts = ['roles' => 'array'];

    public function sub_menus()
    {
        return $this->belongsToMany('App\Models\SubMenu');
    }
}
