<?php
//Can be set to false if method is not allowed
$xhprof_config['enable_key']['get'] = 'XHPROF';
$xhprof_config['enable_key']['post'] = $xhprof_config['enable_key']['get'];
$xhprof_config['enable_key']['cookie'] = $xhprof_config['enable_key']['get'];

//Can be set to false if method is not allowed
$xhprof_config['namespace_key']['get'] = 'XHPROFNS';
$xhprof_config['namespace_key']['post'] = $xhprof_config['enable_key']['get'];
$xhprof_config['namespace_key']['cookie'] = $xhprof_config['enable_key']['get'];

//Allowed ip as they can be read in $_SERVER['REMOTE_ADDR']
$xhprof_config['allowed_ip'][] = '127.0.0.0/8';          //Needed for CLI usage
$xhprof_config['allowed_ip'][] = '10.0.0.0/8';           //Local network
$xhprof_config['allowed_ip'][] = '172.16.0.0/12';        //Local network
$xhprof_config['allowed_ip'][] = '192.168.0.0/16';       //Local network

//Base URL of XHPROF
$xhprof_config['base_url'] = 'http://xhprof.local.agence-tbd.com';

if (empty($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

function get_xhprof_lib_path(){
    return '/usr/share/php/xhprof_lib/';
}

function is_xhprof_enabled()
{
    global $xhprof_config;
    mkdir(ini_get('xhprof.output_dir'),0777,true);
    return xhprof_extension_loaded() && xhprof_enable_key() && xhprof_allowed_ip();
}

function test_xhprof_enabled()
{
    ini_set('display_errors', 1);
    global $xhprof_config;
    echo '<br />Extension Loaded:' . (xhprof_extension_loaded() ? 'y' : 'n');
    echo '<br />Get Found:' . (xhprof_enable_key_get() ? 'y' : 'n');
    echo '<br />Post Found:' . (xhprof_enable_key_post() ? 'y' : 'n');
    echo '<br />Cookie Found:' . (xhprof_enable_key_cookie() ? 'y' : 'n');
    echo '<br />IP:' . $_SERVER['REMOTE_ADDR'] . ' Allowed:' . (xhprof_allowed_ip() ? 'y' : 'n');
}

function xhprof_extension_loaded()
{
    global $xhprof_config;
    return extension_loaded('xhprof');
}

function xhprof_enable_key()
{
    global $xhprof_config;
    return xhprof_enable_key_get() || xhprof_enable_key_post() || xhprof_enable_key_cookie();
}

function xhprof_enable_key_get()
{
    global $xhprof_config;
    return ($xhprof_config['enable_key']['get'] && isset($_GET[$xhprof_config['enable_key']['get']]) && $_GET[$xhprof_config['enable_key']['get']]);
}

function xhprof_enable_key_post()
{
    global $xhprof_config;
    return ($xhprof_config['enable_key']['post'] && isset($_POST[$xhprof_config['enable_key']['post']]) && $_POST[$xhprof_config['enable_key']['post']]);
}

function xhprof_enable_key_cookie()
{
    global $xhprof_config;
    return ($xhprof_config['enable_key']['cookie'] && isset($_COOKIE[$xhprof_config['enable_key']['cookie']]) && $_COOKIE[$xhprof_config['enable_key']['cookie']]);
}

function xhprof_allowed_ip()
{
    global $xhprof_config;
    $ip = ip2long($_SERVER['REMOTE_ADDR']);
    foreach ($xhprof_config['allowed_ip'] as $range) {
        if (strstr($range, '/') !== false) {
            $range = explode('/', $range);
            $corr = (pow(2, 32) - 1) - (pow(2, 32 - $range[1]) - 1);
            $first = ip2long($range[0]) & ($corr);
            $length = pow(2, 32 - $range[1]) - 1;
            if ($ip >= $first || $ip <= ($first + $length)) {
                return true;
            }
        } else {
            if ($ip == ip2long($range)) {
                return true;
            }
        }
    }
    return false;
}

function xhprof_profiler_namespace()
{
    $namespace = '';
    if ($get = xhprof_profiler_namespace_get()) {
        $namespace = $get . '_';
    } elseif ($post = xhprof_profiler_namespace_post()) {
        $namespace = $post . '_';
    } elseif ($cookie = xhprof_profiler_namespace_cookie()) {
        $namespace = $cookie . '_';
    }
    return $namespace . $_SERVER['HTTP_HOST'];
}

function xhprof_profiler_namespace_get()
{
    global $xhprof_config;
    if ($xhprof_config['namespace_key']['get'] && isset($_GET[$xhprof_config['namespace_key']['get']])) {
        return $_GET[$xhprof_config['namespace_key']['get']];
    }
    return false;
}

function xhprof_profiler_namespace_post()
{
    global $xhprof_config;
    if ($xhprof_config['namespace_key']['post'] && isset($_POST[$xhprof_config['namespace_key']['post']])) {
        return $_POST[$xhprof_config['namespace_key']['post']];
    }
    return false;
}

function xhprof_profiler_namespace_cookie()
{
    global $xhprof_config;
    if ($xhprof_config['namespace_key']['cookie'] && isset($_COOKIE[$xhprof_config['namespace_key']['cookie']])) {
        return $_COOKIE[$xhprof_config['namespace_key']['cookie']];
    }
    return false;
}

function xhprof_base_url()
{
    global $xhprof_config;
    return $xhprof_config['base_url'];
}
