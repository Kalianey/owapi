<?php

/**
 * Description of Device Ctrl Class
 *
 * @author Kalianey
 */

class OWAPI_CTRL_Device extends OWAPI_CLASS_ApiController
{
    function register()
    {
        $deviceDao = OWAPI_BOL_DeviceDao::getInstance();
        $user = OW::getUser();
        if (!$user->isAuthenticated())
        {
            $this->error('User not authenticated');
        }
        
        if (!OW::getRequest()->isPost())
        {
            $this->error('This method is available only via POST request');
        }
        
        if (!isset($_POST['token']) || strlen($_POST['token']) == 0)
        {
            $this->error('Device token is required');
        }

        if (!isset($_POST['kind']) || strlen($_POST['kind']) == 0)
        {
            $this->error('Device kind is required');
        }

        $token = $_POST['token'];
        $device = $deviceDao->findByToken($token);
        if(is_null($device))
        {
            $device = new OWAPI_BOL_Device();
            $device->token = $token;
            $device->kind = $_POST['kind'];
        }

        $device->userId = $user->getId();
        $device->last_update = time();
        $device->push_enable = true;

        $extra = isset($_POST['extra'])?$_POST['extra']:'';
        if (is_array($extra) || is_object($extra))
        {
            $extra = json_encode($extra);
        }

        $device->extra = $extra;
        $deviceDao->save($device);
            
        $this->success();
        
    }
    
    function deregister()
    {
        $deviceDao = OWAPI_BOL_DeviceDao::getInstance();
        $user = OW::getUser();
        if (!$user->isAuthenticated())
        {
            $this->error('User not authenticated');
        }
        
        if (!OW::getRequest()->isPost())
        {
            $this->error('This method is available only via POST request');
        }
        
        if (!isset($_POST['token']) || strlen($_POST['token']) == 0)
        {
            $this->error('Device token is required');
        }

        $token = $_POST['token'];
        $device = $deviceDao->findByToken($token);
        if(!is_null($device))
        {
            $device->userId = $user->getId();
            $device->last_update = time();
            $device->push_enable = false;
            $deviceDao->save($device);
        }
        
        $this->success();
        
    }
    
    function test()
    {
        header('Content-type: text/plain');
        $userId = 1;
        
        
        $message = "hello android!";
        $badge=1;
        
        OWAPI_CLASS_Push::getInstance()->sendAll($userId, OWAPI_CLASS_Push::PUSH_TYPE_FRIEND_ACCEPT, $message,$badge);
        
        die();
    }
    
}