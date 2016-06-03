# xhprof-simple-profiler
Simple class to include in order to diagnose a webpage with XHProf

## Usage

You will need to include the profiler.php file and instanciate JbnProfiler()

```php
<?php
require_once 'profiler.php';

new JbnProfiler(array(
    'baseUrl' => 'http://xhprof.mywebsite.dev',
    'baseLibPath' => '/home/nginx/xhprof-0.9.4/xhprof_lib/'
));
```

Within the construct you can override any variable by removing the leading underscore (`'enableKey'` to change `$this->_enableKey`)

```php
<?php
require_once 'profiler.php';

new JbnProfiler(array(
    'baseUrl' => 'http://xhprof.mywebsite.dev',
    'baseLibPath' => '/var/www/xhprof/xhprof-0.9.4/xhprof_lib/',
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