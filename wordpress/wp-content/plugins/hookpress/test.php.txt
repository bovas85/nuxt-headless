<?php
// TEST.PHP
// bundled with HookPress
// mitcho (code@mitcho.com)
// 
// test.php is a simple test script which accepts HTTP requests
// and logs all of the GET and POST parameters accepted. It is useful
// for debugging requests sent by HookPress and is offered purely
// as a tool and toy.
//
// You can move it to http://localhost/test.php, for example, which
// is particularly convenient.
// 
// Make sure that PHP has the ability to create a `test.log` file
// or else create a blank `test.log` file and make sure PHP is able
// to write to it.

$print = print_r($_REQUEST,true);

$log = fopen('test.log', 'a');
fwrite($log, $print);

echo array_shift($_REQUEST);

