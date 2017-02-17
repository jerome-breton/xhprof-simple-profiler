<?php
require_once 'prepend.php';

function foo()
{
    echo "This foo process is processing...<br>\n";
    for ($i = 0; $i < 99999; $i++) {
        rand(0, $i);
    }
    echo "Processing is done.<br>\n";
}

function bar()
{
    echo "The bar process is processing...<br>\n";
    for ($i = 0; $i < 99999; $i++) {
        md5(sha1(str_replace('a', 'b', strrev(str_rot13(base64_encode($i))))));
    }
    echo "Processing is done.<br>\n";
}

function baz()
{
    echo "The baz process is processing...<br>\n";
    for ($i = 3; $i > 0; $i--) {
        sleep($i);
        echo "Bazinga.<br>\n";
    }
    echo "Processing is done.<br>\n";
}

foo();
bar();
baz();