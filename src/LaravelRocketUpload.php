<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 2017/06/07
 * Time: 8:01 PM
 */

namespace IanRothmann\LaravelRocketUpload;


use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class LaravelRocketUpload
{
    private $file;
    private $command;
    private $config=[
        'thumbnail'=>['w'=>128,'h'=>128],
        'primaryKey'=>'fileid',
        'model'=>'App\Models\File',
        'directory'=>'uploadedfiles',
        'private'=>0
    ];
    private $imgMimes=['image/jpg','image/jpeg','image/png','image/gif'];

    /**
     * @var \Closure $imageProcessor
     * @var \Closure $processor
     * @var \Closure $afterUploadClosure
     */
    private $imageProcessor=null;
    private $processor=null;
    private $afterUploadClosure=null;

    public function request(Request $request,$key='file'){
        $this->file=$request->file($key);
        $this->command=$request->get('command');
        return $this;
    }

    public function handle(){
        if($this->command==='delete'){
            return $this->handleDelete();
        }elseif ($this->command==='upload'){
            return $this->handleUpload();
        }
    }

    public function handleDelete(){
        return response('Deleted',200);
    }

    public function thumbnail($width,$height){
        $this->config['thumbnail']['w']=$width;
        $this->config['thumbnail']['h']=$height;
        return $this;
    }

    public function maxDimensions($width,$height){
        $this->config['imgMax']['w']=$width;
        $this->config['imgMax']['h']=$height;
        return $this;
    }

    public function fileModel($modelname){
        $this->config['model']=$modelname;
        return $this;
    }

    public function directory($dirname){
        $this->config['directory']=$dirname;
        return $this;
    }

    public function disk($disk){
        $this->config['disk']=$disk;
        return $this;
    }

    public function privateFile(){
        $this->config['private']=1;
        return $this;
    }

    public function publicFile(){
        $this->config['private']=0;
        return $this;
    }

    private function processDisk(){
        if(!array_key_exists('disk',$this->config)){
            if($this->config['private']==1){
                if(\Config::has('rocketframework.disks.private')){
                    $this->config['disk']=\Config::get('rocketframework.disks.private');
                }else{
                    throw new \Exception("Private disk not specified in rocketframework config.");
                }
            }else{
                if(\Config::has('rocketframework.disks.public')) {
                    $this->config['disk'] = \Config::get('rocketframework.disks.public');
                }
            }
        }
    }

    private function putFile($directory,$file){
        $this->processDisk();
        $disk=null;
        if(array_key_exists('disk',$this->config))
            $disk=$this->config['disk'];

        if($disk){
            return Storage::disk($disk)->putFile($directory,$file);
        }else{
            return Storage::putFile($directory,$file);
        }
    }

    private function put($directory,$filename,$extension,$contents){
        $this->processDisk();
        $disk=null;
        if(array_key_exists('disk',$this->config))
            $disk=$this->config['disk'];

        $fullPath=$directory.'/'.uniqid().'.'.$extension;

        if($disk){
            Storage::disk($disk)->put($fullPath,$contents);
            return $fullPath;
        }else{
            Storage::put($fullPath,$contents);
            return $fullPath;
        }
    }

    private function url($filename){
        $this->processDisk();
        $disk=null;
        if(array_key_exists('disk',$this->config))
            $disk=$this->config['disk'];

         return $disk?Storage::disk($disk)->url($filename):Storage::url($filename);
    }

    public function processImageWith(\Closure $closure){
        $this->imageProcessor=$closure;
        return $this;
    }

    public function processWith(\Closure $closure){
        $this->processor=$closure;
        return $this;
    }

    //func parameters ($file_model)
    public function afterUpload($func){
        $this->afterUploadClosure=$func;
        return $this;
    }

    private function executeAfterUpload($file_model){
        $closure=$this->afterUploadClosure;
        if($closure!=null && is_callable($closure)){
            $closure($file_model);
        }
    }

    public function handleDownload($id){
        $model=$this->config['model'];
        $file=$model::find($id);
        if($file->private){
            if(\Config::has('rocketframework.disks.private')){
                $disk=\Config::get('rocketframework.disks.private');
            }else{
                throw new \Exception("Private disk not specified in rocketframework config.");
            }
            return \Storage::disk($disk)->temporaryUrl($file->filename,Carbon::now()->addMinutes(5));
        }else{
            return $file->url;
        }
    }

    public function handleUpload(){


        if ($this->file->isValid()) {
            if($this->file->getSize()<=$this->file_upload_max_size()){
                $model=$this->config['model'];
                $file=[
                    'mimetype'=>$this->file->getMimeType(),
                    'size'=>$this->file->getSize(),
                    'extension'=>$this->file->getClientOriginalExtension(),
                    'originalfilename'=>$this->file->getClientOriginalName(),
                ];

                $uploadedFile=$this->file;

                if(array_key_exists('maxImg',$this->config)){
                    $resizedFile=Image::make($uploadedFile);
                    $resizedFile->resize($this->config['maxImg']['w'], $this->config['maxImg']['h'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                    $imgFunc=$this->imageProcessor;
                    if($imgFunc!==null && is_callable($imgFunc)){
                        $resizedFile=$imgFunc($resizedFile)->encode();
                    }else{
                        $resizedFile->encode();
                    }

                    $file['filename']=$this->put($this->config['directory'],$file['originalfilename'],$file['extension'],$resizedFile->__toString());
                }else{
                    $imgFunc=$this->imageProcessor;
                    $func=$this->processor;
                    if($imgFunc!==null && is_callable($imgFunc)){
                        $processedFile=Image::make($uploadedFile);
                        $processedFile=$imgFunc($processedFile)->encode();
                        $file['filename']=$this->put($this->config['directory'],$file['originalfilename'],$file['extension'],$processedFile->__toString());
                    }elseif($func!==null && is_callable($func)){
                        $file_contents=file_get_contents($uploadedFile->getRealPath());
                        $file_contents=$func($file_contents);
                        file_put_contents($uploadedFile->getRealPath(),$file_contents);
                        $file['filename']=$this->put($this->config['directory'],$file['originalfilename'],$file['extension'],$file_contents);
                    }else{
                        $file['filename']=$this->putFile($this->config['directory'],$uploadedFile);
                    }

                }

                $file['url']=$this->url($file['filename']);

                if(in_array(trim(strtolower($file['mimetype'])),$this->imgMimes)){
                    $thumbnail=Image::make($this->file)->fit($this->config['thumbnail']['w'], $this->config['thumbnail']['h'])->encode();
                    $thumbFileName=$this->put($this->config['directory'].'/thumbnails',$file['originalfilename'],$file['extension'],$thumbnail->__toString());
                    $file['thumbnail']=$this->url($thumbFileName);
                    $file['thumbnail_filename']=$thumbFileName;
                }
                $file['private']=$this->config['private'];

                $file_model=new $model;
                $file_model->fill($file);
                $file_model->save();
                //$primaryKey=$this->config['primaryKey'];
                //$file_model=$model::find($file_model->$primaryKey);
                $this->executeAfterUpload($file_model);
                return $file_model->fresh();
            }else{
                return response("The file is larger than {$this->human_filesize($this->file->getSize(),2)}.",500);
            }

        }else{
            return response("Something went wrong with the upload.",500);
        }
    }

    function human_filesize($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    function file_upload_max_size() {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $max_size = $this->parse_size(ini_get('post_max_size'));

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }
}