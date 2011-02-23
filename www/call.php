<?php
ini_set ('max_execution_time', 0);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
require_once('../lib/Services.php');
try{
    $services = new Services(array('directory' => 'extracts', 'script' => 'sh ../exec_extract', 'file' => 'url.txt'));
    echo $services->{$_SERVER['REQUEST_METHOD']}($_REQUEST);
} catch (Exception $e) {
    echo sprintf( "{status:error, data:{message:%s}}", $e->getMessage() );
}
