<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 2017/04/12
 * Time: 12:52 PM
 */

namespace IanRothmann\LaravelRocketUpload\ServiceProviders;


use Illuminate\Support\ServiceProvider;

class RocketUploadServiceProvider extends ServiceProvider
{
    public function register(){
        $this->app->bind('rocket-upload','IanRothmann\LaravelRocketUpload\LaravelRocketUpload');

    }


}