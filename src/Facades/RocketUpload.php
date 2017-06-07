<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 2017/04/12
 * Time: 12:50 PM
 */

namespace IanRothmann\LaravelRocketUpload\Facades;


use Illuminate\Support\Facades\Facade;

class RocketUpload extends Facade
{
    public static function getFacadeAccessor(){
        return 'rocket-upload';
    }
}