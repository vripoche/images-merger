<?php
require_once(dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Crawler.php');

if (!empty($argc) && strstr($argv[0], basename(__FILE__))) {
    if ( sizeof($argv) < 2 ) {
        echo "Usage: crawler [url]";
        exit(1);
    }
    $crawlerInstance = new Crawler(array('directory' => dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'extracts'));
    $crawlerInstance->execute( $argv[1] );
    exit(0);
}
?>
