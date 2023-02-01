<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Profile;

class HomeController extends Controller
{
    public function index()
    {
        $owner = Profile::whereUsername('COD(O.T)')->with('users')->get();
        $context = [
            'seo' => [
                'title' => env('APP_BRAND'),
                'canonical' => 'http://codot.com',
            ],
            'brand' => env('APP_BRAND'),
            'env' => env('MIX_APP_ENV'),
            'app_token' => env('MIX_API_TOKEN'),
            'app_url' => env('MIX_APP_URL'),
            'asset_url' => env('MIX_ASSET_URL'),
            'user' => count($owner) > 0 ? $owner : null
        ];
        return view('home.app', $context)->with('context', $context);
    }
}
