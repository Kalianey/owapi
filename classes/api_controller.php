<?php

/* 
* @author Kalianey
*/

class OWAPI_CLASS_ApiController extends OW_ActionController
{   
    
    var $service;
    var $messageDao;
    var $conversationDao;
    var $userDao;
    var $locationService;
    var $attachmentDao;
    var $bridgeService;
    
   
    function __construct() {
        parent::__construct();
        $this->service = MAILBOX_BOL_ConversationService::getInstance();
        $this->messageDao = MAILBOX_BOL_MessageDao::getInstance();
        $this->conversationDao = MAILBOX_BOL_ConversationDao::getInstance();
        $this->userDao = BOL_UserDao::getInstance();
        $this->locationService = GOOGLELOCATION_BOL_LocationService::getInstance();
        $this->attachmentDao = MAILBOX_BOL_AttachmentDao::getInstance();
        $this->bridgeService = OWAPI_CLASS_BridgeService::getInstance();
    }
    
    protected function isAuth($autoQuit = true)
    {
        $isAuth = OW::getUser()->isAuthenticated();
        if (!$isAuth && $autoQuit)
        {
            $this->error('authentication failed');
        }
        return $isAuth;
    }

    protected function success($data=null)
    {
        $result = array(
            'success' => true,
            'data' => $data
        );
        $this->sendResponse($result);
    }
    protected function error($msg)
    {
        $result = array(
            'success' => false,
            'msg' => $msg
        );
        $this->sendResponse($result);
    }
    
    protected function sendResponse($result)
    {
        header('Content-type: application/json');
        echo json_encode($result);
        //echo json_encode($result,JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK); //DEBUG
        exit();
    }
}