<?php

/**
 * Description of Push Class
 *
 * @author Kalianey
 */

class OWAPI_CLASS_Push {
    const PUSH_TYPE_MESSAGE = 'message';
    const PUSH_TYPE_FRIEND = 'friend-request';
    const PUSH_TYPE_FRIEND_ACCEPT = 'friend-request-accepted';
    
    /**
     * Singleton instance.
     *
     * @var OWAPI_CLASS_Push
     */
    private static $classInstance;
    
    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OWAPI_CLASS_Push
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    function sendAll($userId, $type, $message, $badge, $extra=null)
    {
        $pushIos = OWAPI_CLASS_PushIos::getInstance();
        $pushAndroid = OWAPI_CLASS_PushAndroid::getInstance();
        $devices = OWAPI_BOL_DeviceDao::getInstance()->findByUserId($userId);
        //var_dump($devices);
//        OW::getLogger()->addEntry('[PUSH]::sending');
//        OW::getLogger()->writeLog();
//        file_put_contents('push.log', $message."\n");
//        file_put_contents('push.log', "Device count: ".count($devices)."\n" , FILE_APPEND);
//        $i = 0;
        foreach($devices as $device)
        {
            if ($device->push_enable )
            {
                //file_put_contents('push.log', "Device ".$device->kind."(".$device->token.")"."\n" , FILE_APPEND);
                if ($device->kind == OWAPI_BOL_DeviceDao::DEVICE_KIND_IOS)
                {
                    $pushIos->send($device->token, $type, $message, $badge, $extra);
                }
                else if ($device->kind == OWAPI_BOL_DeviceDao::DEVICE_KIND_ANDROID)
                {
                    $pushAndroid->send($device->token, $type, $message, $extra);
                    
                }
            }
        }
    }


}