<?php
namespace IanRothmann\LaravelRocketUpload;

trait RocketFileModelTrait
{
    public function getUrlAttribute($url){
        if($this->private==1){
            if(\Config::has('rocketframework.disks.private')){
                $disk=\Config::get('rocketframework.disks.private');
            }else{
                throw new \Exception("Private disk not specified in rocketframework config.");
            }
            return \Storage::disk($disk)->temporaryUrl($this->filename,Carbon::now()->addMinutes(5));
        }else{
            return $url;
        }

    }

    public function getThumbnailAttribute($url){
        if($this->private==1){
            if(\Config::has('rocketframework.disks.private')){
                $disk=\Config::get('rocketframework.disks.private');
            }else{
                throw new \Exception("Private disk not specified in rocketframework config.");
            }
            return \Storage::disk($disk)->temporaryUrl($this->thumbnail_filename,Carbon::now()->addMinutes(5));
        }else{
            return $url;
        }

    }
}