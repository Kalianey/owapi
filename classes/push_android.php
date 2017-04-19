<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OWAPI_CLASS_PushAndroid
 *
 * @author Kalianey
 */


class OWAPI_CLASS_PushAndroid {
    
    const API_ACCESS_KEY = 'YOUR_ACCESS_KEY';
    
    /**
     * Singleton instance.
     *
     * @var OWAPI_CLASS_PushAndroid
     */
    private static $classInstance;
    
    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OWAPI_CLASS_PushAndroid
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
        

    }
    
    function connect()
    {
        $enabled = OW::getConfig()->getValue('owapi', 'android_push_is_enable');
        
        if (!$enabled)
        {
            return false;
        }
        
    }
    
    //$kind is the type of notification: chat, event...
    function send($token, $type, $message, $extra=null)
    {
        $registrationIds = array( $token );
        // prep the bundle
        $msg = array
        (
            'message' 	=> $message,
            'type'		=> $type,
            'extra'	    => $extra,
            'vibrate'	=> 1,
            'sound'		=> 1,
            'largeIcon'	=> 'large_icon',
            'smallIcon'	=> 'small_icon'
        );
        
        $url = 'https://android.googleapis.com/gcm/send';
        $fields = array(
            'registration_ids' => $registrationIds,
            'data' => $msg,
        );
		// Update your Google Cloud Messaging API Key
        //print_r($fields);
        $headers = array(
            'Authorization: key=' . self::API_ACCESS_KEY,
            'Content-Type: application/json'
        );
        //print_r($headers);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);	
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);				
        if ($result === FALSE) {
            return false;
            //die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);
        //echo $result;
        return $result;
    }

    
    function __destruct() 
    {
       
    }
}
