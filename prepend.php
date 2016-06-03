<?php
require_once 'profiler.php';

new JbnProfiler(array(
    'allowedIp' => array(
        '10.0.0.0/8',           //Local network
        '172.16.0.0/12',        //Local network
        '192.168.0.0/16',       //Local network
    ),
    'enableKey' => 'xhprof'
));