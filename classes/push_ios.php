<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OWAPI_CLASS_ApplePush
 *
 * @author Kalianey
 */

require_once OW::getPluginManager()->getPlugin('owapi')->getRootDir().'lib/ApnsPHP/Autoload.php';

class OWAPI_CLASS_PushIos {
    
    var $pathList;
    
    /* @var $push ApnsPHP_Push */
    var $push;
    var $isConnected;
    
    /**
     * Singleton instance.
     *
     * @var OWAPI_CLASS_PushIos
     */
    private static $classInstance;
    
    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OWAPI_CLASS_PushIos
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    
    function __construct() {
        
        $this->isConnected = false;
        $this->connect();
    }
    
    function connect($sandbox=null,$certPath=null)
    {
        $enabled = OW::getConfig()->getValue('owapi', 'ios_push_is_enable');
        
        if (!$enabled)
        {
            return false;
        }
        
        if($this->isConnected)
        {
            return true;
        }
        
        if (is_null($sandbox))
        {
            $sandbox = OW::getConfig()->getValue('owapi', 'ios_push_is_sandbox');
        }
        
        if (is_null($certPath))
        {
            $certPath = OW::getConfig()->getValue('owapi', 'ios_push_cert_path');
        }
        
        $this->pathLib = OW::getPluginManager()->getPlugin('owapi')->getRootDir().'lib/ApnsPHP/';
        
        if(file_exists($certPath))
        {
            $this->push = new ApnsPHP_Push($sandbox,$certPath);
            $this->push->setLogger(new OWAPI_CLASS_PushIosLogger());
            $this->push->setRootCertificationAuthority($this->pathLib.'entrust_2048_ca.cer');
        
            try
            {
                $this->push->connect();   
                $this->isConnected = true;
            }
            catch(Exception $e)
            {
                $this->isConnected = false;
            }
        }
        
        return $this->isConnected; 
    }
    
    //$kind is the type of notification: chat, event...
    function send($token, $kind, $message, $badge, $extra=null)
    {
        if($this->isConnected)
        {
            $msg = new ApnsPHP_Message($token);
            $msg->setText($message);
            $msg->setBadge($badge);
            $msg->setCustomIdentifier('kind',$kind);
            $msg->setCustomProperty('kind', $kind);
            if (!is_null($extra))
            {
                $msg->setCustomProperty('extra', $extra);
            }
            $this->push->add($msg);
        }
    }

    
    function __destruct() 
    {
        if($this->isConnected)
        {
            try
            {
                $this->push->send();
            }
            catch (Exception $e)
            {
                
            }
        }
    }
}

class OWAPI_CLASS_PushIosLogger implements ApnsPHP_Log_Interface
{
    public function log($sMessage) {
        OW::getLogger()->addEntry($sMessage);
    }

}