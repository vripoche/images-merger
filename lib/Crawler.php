<?php

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ThumbLib.inc.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'SimpleImage.php');

/**
 * Crawler 
 * 
 * @package 
 * @version 1.O
 * @copyright 2011 Ascii aka Vivien Ripoche
 * @author Ascii <contact@vivien-ripoche.fr> 
 * @license PHP Version 5.3 {@link http://www.php.net/license/3_0.txt}
 */
class Crawler
{

    private $_paramSearchDepth = -1;
    private $_paramMaxUrlList  = 1000;
    private $_paramMaxImages   = 200;
    private $_paramDirectory   = "www/extracts";
    private $_paramThumbsize   = "100x100";
    private $_paramImagesize   = "1000x760";
    private $_paramFile        = "url.txt";

    private $_started  = false;
    private $_urlsList = array();

    /**
     * __construct 
     * 
     * @param mixed $params 
     * @access public
     * @return void
     */
    public function __construct($params = null)
    {
       if( $params && is_array($params) ) { 
            foreach($params as $key => $value) {
                if(  property_exists($this, "_param" . ucfirst($key) ) ) {
                    $this->{ "_param" . ucfirst($key) } = $value;
                }
            }
        }
    }

    /**
     * getUrlsList 
     * 
     * @access public
     * @return void
     */
    public function getUrlsList() 
    {
        return $this->_urlsList;
    }

    /**
     * execute 
     * 
     * @param mixed $url 
     * @access public
     * @return void
     */
    public function execute($url)
    {
        $this->_started = false;

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'];
        $hostname = $parsed['host'];

        $this->_saveUrl($url);

        $this->_parse(self::_addHostname($url, $scheme, $hostname), 0, $scheme, $hostname);
    }

    /**
     * _parse 
     * 
     * @param mixed $url 
     * @param mixed $currentDepth 
     * @param mixed $scheme 
     * @param mixed $hostname 
     * @param string $contentType 
     * @access private
     * @return void
     */
    private function _parse($url, $currentDepth, $scheme, $hostname, $contentType = 'text/html')
    {
        $urlsList = null;
        if(! $this->_started) {
            $this->_started = true;
        }else{
            $currentDepth++;
        }

        if($currentDepth < $this->_paramSearchDepth || $this->_paramSearchDepth == -1) {
            $data = self::_getContent($url, $contentType);
            if( $data && preg_match_all('#(src|href)\=[\"]*([a-zA-Z0-9\_\-\?\=\&\/\:\.]+)#i', $data, $urlsList) && strstr($data, 'tumblr') ) {
                if( isset($urlsList[2]) ) {
                    foreach( array('image', 'text/html') as $contentType ) {
                        foreach( $urlsList[2] as $k => $v ) {
                            $check = null;
                            $v = self::_addHostname($v, $scheme, $hostname);
                            if( filter_var($v , FILTER_VALIDATE_URL) && self::_isUrl($v) && $this->_getContent($v)) {
                                $check = get_headers($v, 1);
                            }
                            if( $check && strstr( $check[0], 'OK' ) && array_search($v, $this->_urlsList) === false) {
                                if(strstr($check['Content-Type'], $contentType) && $contentType == 'image') {
                                    $this->_addImage($v, $check['Content-Type']); 
                                    $this->_addUrl($v);
                                } else if(strstr($check['Content-Type'], $contentType) && $contentType == 'text/html') {
                                    usleep(500);
                                    $this->_addUrl($v);
                                    $this->_parse($v, $currentDepth, $scheme, $hostname, $check['Content-Type']);
                                }
                                if( ! $this->_checkUrl() ) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * _saveUrl 
     * 
     * @param mixed $url 
     * @access private
     * @return void
     */
    private function _saveUrl($url) {
        $handle = fopen($this->_paramDirectory . DIRECTORY_SEPARATOR . $this->_paramFile, 'w');
        fputs($handle, $url);
        fclose($handle);
        chmod( $this->_paramDirectory . DIRECTORY_SEPARATOR . $this->_paramFile, 0777 );
    }

    /**
     * _checkUrl 
     * 
     * @access private
     * @return void
     */
    private function _checkUrl() {
        if( file_exists( $this->_paramDirectory . DIRECTORY_SEPARATOR . $this->_paramFile ) ) return true;
        return false;
    }

    /**
     * _addImage 
     * 
     * @param mixed $url 
     * @param mixed $contentType 
     * @access private
     * @return void
     */
    private function _addImage($url, $contentType) 
    {
        if( file_exists( $this->_paramDirectory ) && 
            is_dir( $this->_paramDirectory ) && 
            is_writable( $this->_paramDirectory ) ) {

            $image = self::_getContent($url, $contentType);
            $contentTypeList = split( "/", $contentType );
            $contentTypeList[1] = $contentTypeList[1] == 'jpeg' ? 'jpg' : $contentTypeList[1];
            $timestamp = time();
            $imageName = $this->_paramDirectory . DIRECTORY_SEPARATOR . 'image_' . $timestamp . '.' . $contentTypeList[1];
            $thumbName = $this->_paramDirectory . DIRECTORY_SEPARATOR . 'thumb_' . $timestamp . '.' . $contentTypeList[1];
            $tmpName = $this->_paramDirectory . DIRECTORY_SEPARATOR . 'tmp_' . $timestamp . '.' . $contentTypeList[1];
            $handle = fopen($tmpName, 'w');
            fwrite( $handle, $image );
            fclose($handle);

            $thumbSize = split('x', $this->_paramThumbsize);
            $imageSize = split('x', $this->_paramImagesize);

            $image = new SimpleImage();
            $image->load($tmpName);
            if($image->getWidth() > $image->getHeight()) {
                $image->resizeToHeight($imageSize[1]);
            } else {
                $image->resizeToWidth($imageSize[0]);
            }
            $image->save($tmpName);

            try{
                $image = PhpThumbFactory::create($tmpName);
                $thumb = PhpThumbFactory::create($tmpName);
                $image->adaptiveResize($imageSize[0], $imageSize[1])->save($imageName);
                $thumb->adaptiveResize($thumbSize[0], $thumbSize[1])->save($thumbName);
                unlink($tmpName);
            } catch (Exception $e) {
                throw $e;
            }

            $this->_deleteOlderImage(); 
            
        }
    } 

    /**
     * _deleteOlderImage 
     * 
     * @access private
     * @return void
     */
    private function _deleteOlderImage() {
        $imagesList = glob( $this->_paramDirectory . DIRECTORY_SEPARATOR . "image_*" );
        if( sizeof( $imagesList ) > $this->_paramMaxImages ) {
            asort( $imagesList );
            $olderImage = array_shift($imagesList);
            $olderThumb = str_replace('image_', 'thumb_', $olderImage);
            if(file_exists( $olderImage ) && is_writable( $olderImage ) )
                unlink( $olderImage );
            if(file_exists( $olderThumb ) && is_writable( $olderThumb ) )
                unlink( $olderThumb );
        }
    }

    /**
     * _addUrl 
     * 
     * @param mixed $url 
     * @access private
     * @return void
     */
    private function _addUrl($url) 
    {
        if(sizeof($this->_urlsList) > $this->_paramMaxUrlList) {
            array_shift($this->_urlsList);
        }
        $this->_urlsList[] = $url;
    }    

    /**
     * _addHostname 
     * 
     * @param mixed $url 
     * @param mixed $scheme 
     * @param mixed $hostname 
     * @static
     * @access private
     * @return void
     */
    private static function _addHostname($url, $scheme, $hostname)
    {
        $query = null;
        $url = str_replace(array('"', "'", " "), null, $url);
        $parsed = parse_url($url);

        if( ! isset( $parsed["scheme"] ) ) {
            $parsed["scheme"] = $scheme;
        }
        if( ! isset( $parsed["host"] ) ) {
            $parsed["host"] = $hostname;
        }
        if( isset( $parsed["query"] ) ) {
            $query .= '?' . $parsed["query"];
        }

        $url = sprintf("%s://%s%s%s", $parsed["scheme"],  $parsed["host"],  $parsed["path"], $query);
        return $url;
    }

    /**
     * _stripslashesDeep 
     * 
     * @param mixed $value 
     * @static
     * @access private
     * @return void
     */
    private static function _stripslashesDeep($value) 
    {
        return is_array($value) ? array_map(array('Crawler', '_stripslashesDeep'), $value) : stripslashes($value);
    }

    /**
     * _isUrl 
     * 
     * @param mixed $url 
     * @static
     * @access private
     * @return void
     */
    private static function _isUrl($url) {
        $urlregex = "^(http|https)?\:\/\/";
        $urlregex .= "([a-z0-9+!*(),;?&=$\_.-]+(\:[a-z0-9+!*(),;?&=$\_.-]+)?@)?";
        $urlregex .= "[a-z0-9+$\_-]+(\.[a-z0-9+$\_-]+)*"; 
        $urlregex .= "(\:[0-9]{2,5})?";
        $urlregex .= "(\/([a-z0-9+$\_-]\.?)+)*\/?";
        $urlregex .= "(\?[a-z+&$\_.-][a-z0-9;:@/&%=+$\_.-]*)?";
        $urlregex .= "(#[a-z_.-][a-z0-9+$\_.-]*)?$";
        return eregi($urlregex, $url)?true:false;
    }

    /**
     * _getContent 
     * 
     * @param mixed $url 
     * @param mixed $contentType 
     * @static
     * @access private
     * @return void
     */
    private static function _getContent($url, $contentType = 'binary') 
    {
        $handle   = curl_init($url);
        if (false === $handle) {
            return false;
        }
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") );
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        if( strstr( $contentType, 'image' ) ) {
            curl_setopt($handle, CURLOPT_BINARYTRANSFER, true);
        }

        $page = curl_exec($handle);

        if( curl_errno($handle) ) {
            $page = null;
        }

        curl_close($handle);

        if( strstr( $contentType, 'text' ) ) {
            $page = preg_replace(array("#\n#", "#\r#", "#\s#"), null, $page);
        }

        return $page;
    }
}
