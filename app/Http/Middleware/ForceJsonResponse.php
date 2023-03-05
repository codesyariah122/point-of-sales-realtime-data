<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Login;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $request_token = $request->headers->get('Authorization');
        $explode_token = explode(' ',$request_token);
        // var_dump(count($explode_token)); die;
        $user_token_login = count($explode_token) > 1 ? $explode_token[1] : NULL;

        $user = Login::where('user_token_login', $user_token_login)->get();

        if(count($user) > 0) {
            // $request->headers->set('Accept', 'application/json');
            return $next($request);
        } else {
            return $next($request);
        }

    }
}
