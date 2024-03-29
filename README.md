# simple-profiler
Simple class to include in order to diagnose a PHP webpage with XHProf or Tideways. An open-source alternative to Blackfire.io.

An XHProf-fork extension must be installed. Nowadays (end of 2022) :
- [Tideways](https://github.com/tideways/php-profiler-extension) supports PHP 7.x and PHP 8.0 and is not maintained anymore ;
- [UProfiler](https://github.com/FriendsOfPHP/uprofiler) is compatible with PHP 5.6 and lower and is not maintained anymore ;
- [XHProf](https://pecl.php.net/package/xhprof) is compatible with PHP 7.2 to PHP 8.2.

## Usage

The `/html` folder must be HTTP viewable. The base url for this folder must then be given when you include the profiler.php file and instanciate JbnProfiler()

```php
<?php
require_once 'profiler.php';

new JbnProfiler(array(
    'baseUrl' => 'http://xhprof.mywebsite.dev',
));
```

Within the construct you can override any variable by removing the leading underscore (`'enableKey'` to change `$this->_enableKey`)

```php
<?php
require_once 'profiler.php';

new JbnProfiler(array(
    'baseUrl' => 'http://xhprof.mywebsite.dev',
    'allowedIp' => array(
        '10.0.0.0/8',           //Local network
        '172.16.0.0/12',        //Local network
        '192.168.0.0/16',       //Local network
    ),
    'enableKey' => 'xhprof'
));
```

List of parameters is detailed in class comments.

You can put those instructions in a prepend.php file that you can include in your php file to diagnose
or via [auto_prepend_file](http://php.net/manual/ini.core.php#ini.auto-prepend-file) directive in your php.ini

## Visualisation only

This project can also be used to view XHProf traces built by another way (ie : you can get traces from [Quanta](https://www.quanta-computing.com/)).

The faster way to do this is to use [Docker](https://www.docker.com/) :

1. Install Docker
2. Checkout this project
3. Build the docker image

        docker build -t jeromebreton/xhprof-simple-viewer:source .
4. Create and run the container

        docker run --rm -p 3731:80 --name jeromebreton-xhprof-simple-viewer -v "`pwd`/traces":/traces jeromebreton/xhprof-simple-viewer:source
5. Put your XHProf traces in the `traces` folder
6. Open your browser to http://127.0.0.1:3731

To make it easier, steps 3 and 4 are packaged in `./docker-build-n-run.sh`

You can use environment variables `PROFILER_PATH` and `PROFILER_SUFFIX` to use different folders or file extension (defaults to /traces/*.xhprof)

## Note
The two folders `html` and `lib` are mainly extracted from `xhprof_html` and `xhprof_lib` folders of the original [XHProf project](https://github.com/phacility/xhprof) because Tideways had stripped it.
