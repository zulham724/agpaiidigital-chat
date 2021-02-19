<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use \Firebase\JWT\JWT;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $jwt = $request->query('token');
            if(empty($jwt)){
                $key = explode(' ',$request->header('Authorization'));
                if(isset($key[1])){
                    $jwt = $key[1];
                }else return null;

            }
            $publicKey = file_get_contents(__DIR__."/../../oauth-public.key");
            $decoded = JWT::decode($jwt, $publicKey, array('RS256'));
            // $decoded_array = (array) $decoded;
            $this->app['decoded_array'] =  $request->decoded_array = (array) $decoded;
            
            return true;
        });
    }
}
