<?php
/**
 * Services 
 * 
 * @package 
 * @version $id$
 * @copyright 2011 Ascii aka Vivien Ripoche
 * @author Ascii <contact@vivien-ripoche.fr> 
 * @license PHP Version 5.3 {@link http://www.php.net/license/3_0.txt}
 */
class Services
{
    private $_paramDirectory = null;
    private $_paramScript = null;
    private $_paramFile = null;

    /**
     * __construct 
     * 
     * @param mixed $params 
     * @access public
     * @return void
     */
    public function __construct($params = null) {
        if( $params && is_array($params) ) {
            foreach($params as $key => $value) {
                if(  property_exists($this, "_param" . ucfirst($key) ) ) {
                    $this->{ "_param" . ucfirst($key) } = $value;
                }
            }
        }
    }

    /**
     * __call 
     * 
     * @param mixed $method 
     * @param mixed $arguments 
     * @access public
     * @return void
     */
    public function __call($method, $arguments) {
        if( ! empty( $arguments ) ) {
            $arguments = array_shift($arguments);
        }
        $name = null;
        if( isset ($arguments['name']) ) {
            $name = $arguments['name'];
            unset($arguments['name']);
        }
        $methodName = '_' . strtolower($method) . ucfirst($name);
        if( method_exists($this, $methodName ) ) {
            try {
                return json_encode(array('status' => 'success', 'data' => $this->{ $methodName }($arguments)));
            } catch( Exception $e ) {
                throw $e;
            }
        } else {
            throw new Exception("Service does not exist");
        }
    }

    /**
     * _get 
     * 
     * @param mixed $arguments 
     * @access private
     * @return void
     */
    private function _get($arguments) {
        if( true && file_exists( $this->_paramDirectory ) && is_dir( $this->_paramDirectory ) ) {
            $imagesList = glob( $this->_paramDirectory . DIRECTORY_SEPARATOR . "image_*" );
            if (! sizeof( $imagesList ) ) throw new Exception("No image in the directory");
            $thumbsList = glob( $this->_paramDirectory . DIRECTORY_SEPARATOR . "thumb_*" );
            $key = rand(0, sizeof($imagesList) - 1);
            return array("image" => basename( $imagesList[$key]), "thumb" => basename( $thumbsList[$key]) );
        }
        throw new Exception("Directory does not exist");
    }

    /**
     * _post 
     * 
     * @param mixed $arguments 
     * @access private
     * @return void
     */
    private function _post($arguments) {
        if( $arguments['action'] == 'start' && ! $this->_checkUrl() ) {
            $returnValue = exec(sprintf('%s "%s"', $this->_paramScript, $arguments['url']));
            if( $returnValue == 'error' ) {
                throw new Exception("Extract Script error");
            }
        } else { 
            $this->_deleteUrl();
        }
        return array();
    }

    /**
     * _delete 
     * 
     * @param mixed $arguments 
     * @access private
     * @return void
     */
    private function _postDelete($arguments) {
        foreach( array('image', 'thumb') as $arg ) {
            if( isset( $arguments[$arg] ) && 
                file_exists( $this->_paramDirectory . DIRECTORY_SEPARATOR . $arguments[$arg] ) && 
                is_file( $this->_paramDirectory . DIRECTORY_SEPARATOR . $arguments[$arg] ) && 
                is_writable( $this->_paramDirectory . DIRECTORY_SEPARATOR . $arguments[$arg] ) ) {

                unlink($this->_paramDirectory . DIRECTORY_SEPARATOR . $arguments[$arg]);
            } else {
                throw new Exception("Image cannot be deleted");
            }
        }
        return array();
    }

    /**
     * _deleteUrl 
     * 
     * @access private
     * @return void
     */
    private function _deleteUrl() {
        if( file_exists( $this->_paramDirectory . DIRECTORY_SEPARATOR . $this->_paramFile ) ) {
            unlink($this->_paramDirectory . DIRECTORY_SEPARATOR . $this->_paramFile);
        }
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



}
