<?php

/**
 * Description of Messenger Ctrl Class
 *
 * @author Kalianey
 */

class OWAPI_CTRL_Messenger extends OWAPI_CLASS_ApiController
{
    
    function conversationList() {
        
        $userId = OW::getUser()->getId(); 
        
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $conversations = $conversationService->getConversationListByUserId(OW::getUser()->getId());
        
        if (is_null($conversations))
        {
            $this->error('Conversations not found');
            
        } else {
            
            $this->success($conversations);
        }
        
    }

    private function isUserConversation($conversationId)
    {
        $userId = OW::getUser()->getId();
        $conversation = $this->conversationDao->findById($conversationId);
        if (is_null($conversation))
        {
            return false;
        }
        
        return $conversation->initiatorId == $userId || $conversation->interlocutorId == $userId;
    }
            
    function contactList()
    {
        $userId = OW::getUser()->getId(); 
        
        $userOnlineListData = $this->service->getUserOnlineList($userId);
        $userListData = $this->service->getUserList($userId, $userOnlineListData['userIdList']);
        
        for($i = 0; $i < count($userListData['list']); $i++) {
            $userListData['list'][$i]['displayName'] = htmlspecialchars_decode($userListData['list'][$i]['displayName']);
            $userId = $userListData['list'][$i]['opponentId'];
            $userInfo = $this->bridgeService->getUserInfo($userId);
            $userListData['list'][$i]['coverUrl'] = $userInfo['cover_url'];
            $userListData['list'][$i]['age'] = $userInfo['age'];
            $userListData['list'][$i]['sex'] = $userInfo['sex'];
            $userListData['list'][$i]['address'] = $userInfo['address'];
        } 
        
        $this->success($userListData);
    }
    
    function conversation($params)
    {
        $userId = OW::getUser()->getId();
        
        $conversationId = $params['conversationId'];
        if ( !$this->isUserConversation($conversationId) )
        {
            $this->error('Conversation not found');
        }
        else        
        {
            $conversation = $this->conversationDao->findById($conversationId);
            $messages = $this->messageDao->findListByConversationId($conversationId, 50);
            $attachments = $this->attachmentDao->findAttachmentstByConversationList(array($conversationId));
            
            for($i=0;$i<count($messages);$i++)
            {
                $messages[$i] = (array)$messages[$i];
            }
            
            $attachmentsByMessage = array();
            foreach($attachments as $attachment)
            {
                $attachmentsByMessage[$attachment['messageId']] = $attachment;
            }
            
            foreach($messages as $key=>$message)
            {
                //we remplace oembed code by simple text
                $text = $messages[$key]['text'];
                if ( substr($text, 0, 1) === '{' ) {
                    $messages[$key]['text'] = json_decode($text);
                    if ($messages[$key]['text']->eventName === 'renderOembed') {
                        $messages[$key]['text'] = $messages[$key]['text']->params->message;
                    }
                }
                
                //we add the attachments if there is one
                if(isset($attachmentsByMessage[$message['id']]))
                {
                    $attachment = $attachmentsByMessage[$message['id']];
                    $ext = UTIL_File::getExtension($attachment['fileName']);
                    $attachmentPath = $this->service->getAttachmentFilePath($attachment['id'], $attachment['hash'], $ext, $attachment['fileName']);
                    $downloadUrl = OW::getStorage()->getFileUrl($attachmentPath);

                    $messages[$key]['attachment'] = array(
                        'id'=>$attachment['id'],
                        'fileName'=>$attachment['fileName'],
                        'fileSize'=>$attachment['fileSize'],
                        'fileExt' => $ext,
                        'downloadUrl'=>$downloadUrl  
                    );
                }
                else
                {
                    $messages[$key]['attachment']= "";
                }
            }
            
            $response = array(
                'conversation'=>$conversation,
                'messages'=>$messages
            );
        
            //We set the conversation as read since the user requested to display it
            //$this->service->markRead(array($conversationId), $userId);
            $this->service->setConversationViewedInConsole(array($conversationId), $userId);
            //$this->service->resetUserLastData($userId);
            
            $this->success($response);
        }
        
    }
    
    function conversationUnread($params)
    {
        $conversationId = $params['conversationId'];
        $lastMessage = $params['lastMessage'];
            
        if ( !$this->isUserConversation($conversationId) )
        {
            $this->error('Conversation not found');
        }
        else        
        {
            $message = $this->messageDao->findById($lastMessage);
            $messages = $this->messageDao->findConversationMessagesByLastMessageTimestamp($conversationId, $message->timeStamp);

            $this->success($messages);
        }
    }
    
    function conversationHistory($params)
    {
        
        $conversationId = $params['conversationId'];
        $lastMessage = $params['lastMessage'];
        
        if ( !$this->isUserConversation($conversationId) )
        {
            $this->error('Conversation not found');
        }
        else        
        {
            $messages = $this->removeOembed($this->messageDao->findHistory($conversationId, $lastMessage, 30));
            
            /* test with attachment */
            
            $attachments = $this->attachmentDao->findAttachmentstByConversationList(array($conversationId));
            
            for($i=0;$i<count($messages);$i++)
            {
                $messages[$i] = (array)$messages[$i];
            }
            
            $attachmentsByMessage = array();
            foreach($attachments as $attachment)
            {
                $attachmentsByMessage[$attachment['messageId']] = $attachment;
            }
            
            foreach($messages as $key=>$message)
            {
                //we remplace oembed code by simple text
                $text = $messages[$key]['text'];
                if ( substr($text, 0, 1) === '{' ) {
                    $messages[$key]['text'] = json_decode($text);
                    if ($messages[$key]['text']->eventName === 'renderOembed') {
                        $messages[$key]['text'] = $messages[$key]['text']->params->message;
                    }
                }
                
                //we add the attachments if there is one
                if(isset($attachmentsByMessage[$message['id']]))
                {
                    $attachment = $attachmentsByMessage[$message['id']];
                    $ext = UTIL_File::getExtension($attachment['fileName']);
                    $attachmentPath = $this->service->getAttachmentFilePath($attachment['id'], $attachment['hash'], $ext, $attachment['fileName']);
                    $downloadUrl = OW::getStorage()->getFileUrl($attachmentPath);

                    $messages[$key]['attachment'] = array(
                        'id'=>$attachment['id'],
                        'fileName'=>$attachment['fileName'],
                        'fileSize'=>$attachment['fileSize'],
                        'fileExt' => $ext,
                        'downloadUrl'=>$downloadUrl  
                    );
                }
                else
                {
                    $messages[$key]['attachment']= "";
                }
            }
            /* End test with attachment */
            
            $this->success($messages);
        }
    }
    
    function removeOembed($messages) {
        
        foreach($messages as $message){
            $text = $message->text;
            if ( substr($text, 0, 1) === '{' ) {
                $message->text = json_decode($text);
                if ($message->text->eventName === 'renderOembed') {
                    $message->text = $message->text->params->message;
                }
            }
        }
                
        return $messages;
    }
    
    
    
    function conversationSend($params)
    {
        $userId = OW::getUser()->getId();
        $conversationId = $params['conversationId'];
       
        $text = $_POST['text'];
        
        if ( !$this->isUserConversation($conversationId) )
        {
            $this->error('Conversation not found');
        }
        else        
        {
            $conversation = $this->service->getConversation($conversationId);
            $newMessage = $this->service->createMessage($conversation, $userId, $text);
            
            //here ask for conversationUnread and return it, but only if there is lastMessage because it means the conversation exists already
            if( isset($_POST['lastMessage']) ) {
                
                $lastMessage = $_POST['lastMessage'];
                $message = $this->messageDao->findById($lastMessage);
                $messages = $this->messageDao->findConversationMessagesByLastMessageTimestamp($conversationId, $message->timeStamp);
                $this->success($messages);
                
            } else {
                
                $this->success($newMessage);
                
            }
        }
  
    }
    
    function getConversationWithOpponentId($params){
        
        $userId = OW::getUser()->getId();
        $opponentId = $params['opponentId'];
        $conversation = $this->conversationDao->findChatConversationIdWithUserById($userId, $opponentId);
        
        if (is_null($conversation))
        {
            $this->error('Conversations not found');
            
        } else {
            
            $this->success($conversation);
        }
        
    }
    
    
    function conversationCreate() {
        
        $initiatorId = $_POST["initiatorId"];
        $interlocutorId = $_POST["interlocutorId"];
        
        $conversation = $this->service->createChatConversation( $initiatorId, $interlocutorId );
        $conversation = $this->conversationDao->getConversationItem($conversation->id);
        
        if (is_null($conversation))
        {
            $this->error('Error');
            
        } else {
            
            $this->success($conversation);
        }
    }
    
    
    function conversationMarkRead( $params ) {
        
        $userId = OW::getUser()->getId();
        //check if 0 dont do it
        $conversationId = $params['conversationId'];
        
        $messageList = $this->messageDao->findUnreadMessagesForConversation($conversationId,$userId);
        foreach ($messageList as $message)
        {
            $this->service->markMessageRead($message->id);
        }
        $this->service->markRead(array($conversationId), $userId);
        $this->service->setConversationViewedInConsole(array($conversationId), $userId);
        
        $unreadConversations=$this->messageDao->findUnreadConversations($userId);
        $countUnread = count($unreadConversations);
        $result = array ('count' => $countUnread );
         
        if (is_null($countUnread))
        {
            $this->error('Error');
            
        } else {
            $this->success($result);
        }
            
    }
    
    
    
}