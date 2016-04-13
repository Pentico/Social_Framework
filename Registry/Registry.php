<?php

/**
 * Created by .....
 * User: Alfie
 * Date: 2016/04/03
 * Time: 10:54 PM
 */


/**
 * Class Registry
 *
 * PHP Social Networking
 */

class Registry
{

    /**
     * Array of objects
     */
    private $objects;

    /**
     * Array of settings
     */
    private $settings;

    public function __construct(){
    }

    /**
     * Create a new object and store it in the registry
     * @param String $object the object file prefix
     * @param String $key pair for the object
     * @return void
     */
    public function createAndStoreObject($object , $key){

        require_once( $object. '.class.php');
        $this ->objects[$key] = new $object($this); //need to check this error online

    }

    /**
     * Store setting
     * @param String $setting the setting data
     * @param String $key the key pair for the settings array
     * @return void
     */
    public function storeSetting($setting, $key){
        $this ->settings[$key] =$setting;
    }

    /**
     * Get a setting from the registries store
     * @param String $key the settings array key
     * @param String the setting  data
     */
    public function getSetting($key){
        return $this->settings[$key];
    }

    /**
     * Get an object from the registries store
     * @param String $key the objects array key
     * @return Object
     */
    public function getObject($key){
        return $this->objects[$key];
    }

}

?>