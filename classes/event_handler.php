<?php


/**
 * Description of event_handler
 *
 * @author Kalianey
 */

class OWAPI_CLASS_EventHandler {
    
    function init()
    {
        $em = OW::getEventManager();
        //when anyone send a message either through site or api the function is called
        $em->bind('mailbox.send_message', array($this,'userSendMessage'));
//        $em->bind('friends.send_friend_request', array($this,'sendFriendRequest'));
//        $em->bind('friends.add_friend',array($this,'sendFriendRequest'));
        $em->bind('friends.request-accepted',array($this,'sendFriendRequestAccepted'));
    }
    
    function sendFriendRequestAccepted(OW_Event $e)
    {
        $command = $e->getName(); 
        
        //friends.request-accepted
         
        //OW::getLogger()->addEntry('push:sendFriendRequest: begin');
        $params = $e->getParams();

        if ( empty($params['recipientId']) || empty($params['senderId']) )
        {
            //var_dump($params);
            return;
        }

        $requesterId = $params['recipientId'];
        $userId = $params['senderId'];
        $questions = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('realname'));
        $realname = $questions[$userId]['realname'];
        
        $message = OW::getLanguage()->text('friends','notify_accept');
        $message = str_replace('{$receiver}', $realname, $message);
        $badge=1;
        
        OWAPI_CLASS_Push::getInstance()->sendAll($userId, OWAPI_CLASS_Push::PUSH_TYPE_FRIEND_ACCEPT, $message,$badge);
    }
    
    function userSendMessage(OW_Event $e)
    {
        $params = $e->getParams();
        $recipientId = $params['recipientId'];
        $senderId = $params['senderId'];
        $questions = BOL_QuestionService::getInstance()->getQuestionData(array($senderId), array('realname'));
        $realname = $questions[$senderId]['realname'];
        $message = $params['message'];
        $userId = OW::getUser()->getId();
        $badge = count(MAILBOX_BOL_MessageDao::getInstance()->findUnreadConversations($userId));
        
        $extra = $params;
        $extra['displayName'] = $realname;
        //unset($extra['message']);
        
        OWAPI_CLASS_Push::getInstance()->sendAll($recipientId, OWAPI_CLASS_Push::PUSH_TYPE_MESSAGE, $message,$badge,$extra);
        
    }
}
