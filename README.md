# LaravelRocketUpload
A Laravel 5 handler for file uploads with the Rocket Upload Vue component.

## Installation

`composer require ianrothmann/laravel-rocket-upload`

in `config/app.php`

Service Provider
` IanRothmann\LaravelRocketUpload\ServiceProviders\RocketUploadServiceProvider::class`

Facade

`'RocketUpload' =>IanRothmann\LaravelRocketUpload\Facades\RocketUpload::class`

## Examples
An example of an Image
```php
return RocketUpload::request($request)
                ->disk('s3') //optional
                ->directory('uploadedfiles')
                ->thumbnail($w,$h,$fit) //$fit=true by default, if false it will resize the picture and not cut off
                ->maxDimensions($w,$h)
                ->processImageWith(function(Image $image){
                     //Intervention Image Object
                     //Do something here
                     return $image;
                  })
                  ->afterUpload(function(File $file){
                     //Do something with file model here
                  })
                ->handle();

```


An example of a file.

```php
return RocketUpload::request($request)
                ->disk('s3') //optional
                ->directory('uploadedfiles')
                ->processWith(function($file_contents){
                     //Do something here
                     return $file_contents;
                  })
                ->handle();

```

Be careful with ->processWith(), it loads the entire file into memory.
