<?php
require_once('../lib/Crawler.php');

if (!empty($argc) && strstr($argv[0], basename(__FILE__))) {
    if ( sizeof($argv) < 2 ) {
        echo "Usage: crawler [url]";
        exit(1);
    }
    $crawlerInstance = new Crawler(array('directory' => '../www/extracts'));
    $crawlerInstance->execute( $argv[1] );
    exit(0);
}
?>
