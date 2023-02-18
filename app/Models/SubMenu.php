<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubMenu extends Model
{
    use HasFactory;

    protected $casts = ['roles' => 'array'];

    public function menus()
    {
        return $this->belongsToMany('App\Models\Menu');
    }
}
