<?php
/**
 * Created by PhpStorm.
 * User: ian
 * Date: 2017/06/07
 * Time: 8:01 PM
 */

namespace IanRothmann\LaravelRocketUpload;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class LaravelRocketUpload
{
    private $file;
    private $command;
    private $config=[
        'thumbnail'=>['w'=>128,'h'=>128],
        'model'=>'App\Models\File',
        'directory'=>'uploadedfiles'
    ];

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

    public function fileModel($modelname){
        $this->config['model']=$modelname;
        return $this;
    }

    public function directory($dirname){
        $this->config['directory']=$dirname;
        return $this;
    }

    public function handleUpload(){
        if ($this->file->isValid()) {
            if($this->file->getSize()<=$this->file_upload_max_size()){
                $model=$this->config['model'];
                $file=[
                    'filename'=>$this->file->store($this->config['directory']),
                    'mimetype'=>$this->file->getMimeType(),
                    'size'=>$this->file->getSize(),
                    'extension'=>$this->file->getClientOriginalExtension(),
                    'originalfilename'=>$this->file->getClientOriginalName(),
                ];
                $file['url']=Storage::url($file['filename']);

                if(stripos($file['mimetype'],'image')!==FALSE)
                    $file['thumbnail'] = Image::make($this->file)->fit($this->config['thumbnail']['w'], $this->config['thumbnail']['h'])->encode('data-url')->encoded;

                $file_model=new $model;
                $file_model->fill($file);
                $file_model->save();

                return $file_model;
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