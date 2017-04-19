<?php

/* 
 * @author Kalianey
 */

class OWAPI_CLASS_BridgeService extends OW_ActionController {
    
     /**
     * Class instance
     *
     * @var OWAPI_CLASS_BridgeService
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return OWAPI_CLASS_BridgeService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getUserInfo($userId){
        
        $questions = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('realname', 'googlemap_location', 'birthdate', 'sex', 'email'));

        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2);
        
        $coverUrl = $this->getSmallCoverForUser($userId);
        
        $address = "";
        if(isset($questions[$userId]['googlemap_location']))
        {
            $address = $questions[$userId]['googlemap_location']["address"];
        }
        
        $ageStr = "";
        if ( !empty($questions[$userId]['birthdate']) )
        {
            $date = UTIL_DateTime::parseDate($questions[$userId]['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
            $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            $ageStr = $age ." ". OW::getLanguage()->text('base', 'questions_age_year_old');
        }
        
        $sexValue = '';
        if ( !empty($questions[$userId]['sex']) )
        {
            $sex = $questions[$userId]['sex'];

            for ( $i = 0; $i < 31; $i++ )
            {
                $val = pow(2, $i);
                if ( (int) $sex & $val )
                {
                    $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                }
            }

            if ( !empty($sexValue) )
            {
                $sexValue = substr($sexValue, 0, -2);
            }
        }
        
        $onlineStatus = BOL_UserService::getInstance()->findOnlineUserById($userId);
        $isOnline = false;
        if (!is_null($onlineStatus)){
            $isOnline = true;
        } 
        
        $out = array (
            'userId' => $userId,
            'name' => $questions[$userId]['realname'],
            'avatar_url' => $avatarUrl,
            'cover_url' => $coverUrl,
            'age' => $ageStr,
            'sex' => $sexValue,
            'email' => $questions[$userId]['email'],
            'address' => $address,
            'isOnline' => $isOnline
        );
        
        return $out;
    }
    
    public function findUserPhotos( $userId, $first, $count )
    {
        
        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $photoDao = PHOTO_BOL_PhotoDao::getInstance();
        $albumDao = PHOTO_BOL_PhotoAlbumDao::getInstance();

        $query = 'SELECT p.* FROM ' . $photoDao->getTableName() . ' AS p
            INNER JOIN ' . $albumDao->getTableName() . ' AS a ON p.albumId=a.id
                WHERE a.userId=:u AND p.status = "approved" ORDER BY p.addDatetime DESC
                    LIMIT :first, :count';

        $list = OW::getDbo()->queryForList($query, array(
            'u' => $userId,
            'first' => $first,
            'count' => $count
        ));

        $out = array();
        foreach ( $list as $photo )
        {
            $id = $photo['id'];
            $out[$id] = array(
                'id' => $id,
                'thumb' => $photoService->getPhotoUrlByType($id, PHOTO_BOL_PhotoService::TYPE_SMALL, $photo["hash"]),
                'url' => $photoService->getPhotoUrlByType($id, PHOTO_BOL_PhotoService::TYPE_MAIN, $photo["hash"]),
                'description' => $photo['description'],
                'permalink' => OW::getRouter()->urlForRoute('view_photo', array(
                    'id' => $id
                ))
            );

        }

        return $out;
    }
    
    
    
    public function findUserFriends($userId, $first, $count){
        
        $friendService = FRIENDS_BOL_Service::getInstance();
        $userService = BOL_UserService::getInstance();
        
        $idList = $friendService->findUserFriendsInList($userId, $first, $count);
        $userList = $userService->findUserListByIdList($idList);

        $friendsList = array();   
        
        for( $i= 0 ; $i < count($userList) ; $i++ ) {

            $userId = $userList[$i]->id;
            $friendsList[$userId]['userId'] = $userId;
            $friendsList[$userId]['username'] = $userList[$i]->username;
            $questions = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('realname'));
            $friendsList[$userId]['name'] =  $questions[$userId]['realname'];
            $friendsList[$userId]['avatar_url'] = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
            
        }
        
        return $friendsList;
    }
    
    
    
    public function getUserViewQuestions( $userId, $adminMode = false, $questionNames = array(), $sectionNames = null )
    {
        $questions = BOL_UserService::getInstance()->getUserViewQuestions($userId, $adminMode, $questionNames, $sectionNames);
        
        $sectionList = array();
        $questionsWithSectionAndValues = array();

        $labels = $questions['labels'];
        $data = $questions['data'];
        $sections = $questions['questions'];
        
        $index = 0;
        
        foreach ($labels as $key => $label) {
            
            //get the question key and label
            $questionsWithSectionAndValues[$index]['questionKey'] = $key;
            $questionsWithSectionAndValues[$index]['questionName'] = $label;
            
            //get the question value
            foreach($data as $k => $value) {
                
                if (isset($data[$k][$key])) {
                    $questionsWithSectionAndValues[$index]['questionValue'] = $data[$k][$key];
                }
            }
            
            //get the section key and label
            foreach($sections as $section => $array) {
                $sectionList[$section] = OW::getLanguage()->text('base', 'questions_section_'.$section.'_label');
                foreach($array as $k => $arr) {
                      
                      if ($array[$k]['name'] == $key) {
                          $questionsWithSectionAndValues[$index]['sectionKey'] = $section;
                          $questionsWithSectionAndValues[$index]['sectionName'] = OW::getLanguage()->text('base', 'questions_section_'.$section.'_label');
                      }

                  } 
            }
            $index++;
                
        }
    
        $questionsBySection = array();
        foreach($questionsWithSectionAndValues as $q)
        {
            $sectionKey = $q['sectionKey'];
            if($sectionKey != "")
            {
                if( !is_array($questionsBySection[$sectionKey]))
                {
                    //by setting the key we regroup them automatically because the array key is unique so it has to be put in the same array
                    $questionsBySection[$sectionKey] = array();
                } 

                $questionsBySection[$sectionKey][]=$q;
            }
        }
        
        //TODO: not a robust solution as the index may change, redo
        $sectionList = array_values($sectionList);
        $questionsBySection = array_values($questionsBySection);
        
        
        $out = array('sections' => $sectionList, 
                     'questionsData' =>$questionsBySection
                    );
        
       return $out;
    }
    
/********************************************************************************************/    
/**************************************** GET DEFAULT COVER *******************************/ /********************************************************************************************/
    public function getSmallCoverForUser($userId){
        
        $owapiFilesDir = OW::getPluginManager()->getPlugin('owapi')->getUserFilesDir();
        $owapiFilesUrl = OW::getPluginManager()->getPlugin('owapi')->getUserFilesUrl();
        $uheaderFilesDir = OW::getPluginManager()->getPlugin('uheader')->getUserFilesDir();
        
        $coverUrl = $this->getCoverForUser($userId, 200);
        
        //echo $coverUrl." is the cover url original path <br/>";
        
        $path = parse_url($coverUrl, PHP_URL_PATH);
        $filenameWithExt = basename($path);
        $imageThumbPath = $owapiFilesDir.'thumb_'.$filenameWithExt;
        $imageThumbUrl = $owapiFilesUrl.'thumb_'.$filenameWithExt;

        if (!file_exists ( $imageThumbPath ))
        {
            //echo 'the image doesnt exist, we create it';
            KYMODS_CLASS_ImageManipulator::getInstance()->cropToFit($uheaderFilesDir.$filenameWithExt, $imageThumbPath, 300, 200);
            //echo '<br/>And then we tinypng it<br/>'; //Maybe to remove depending of image quality
            KYMODS_BOL_Service::getInstance()->tinyPng($owapiFilesDir, 'thumb_'.$filenameWithExt);

        }

        return $imageThumbUrl;
    }
   
    
    public function getCoverForUser( $userId, $forWidth = null )
    {
       $isPluginActive = OW::getPluginManager()->isPluginActive('uheader');
       
       if($isPluginActive) {
            if ( OW::getEventManager()->call("uheader.get_version" ) >= 2 )
            {
                $cover = OW::getEventManager()->call("uheader.get_cover", array(
                    "userId" => $userId
                ));
                return $cover['src'];
            }

            // Backward compatibility
            $dto = $this->findCoverDtoByUserId($userId);
            if ( $dto === null )
            {
                return null;
            }
            $src = $this->getCoverUrl($dto);
            return $src;
       }
    }

    public function findCoverDtoByUserId( $userId )
    {
        if ( !$this->isActive() ) return null;
        
        $permitted = UHEADER_CLASS_PrivacyBridge::getInstance()->checkPrivacy($userId);
        
        if ( !$permitted )
        {
            return null;
        }
        
        return UHEADER_BOL_Service::getInstance()->findCoverByUserId($userId);
    }
    
    public function getCoverUrl( UHEADER_BOL_Cover $cover )
    {
        if ( !$this->isActive() ) return null;
        
        return UHEADER_BOL_Service::getInstance()->getCoverUrl($cover);
    }
    
    
    /***************************************************** USER BRIDGE *******************************************/
    
    public function findUserListByDistance($first, $limit, $distance) {
        
        $userId = OW::getUser()->getId();

        $query = "SELECT 
                    u1.*, 
                    loc1.lat, 
                    loc1.lng, 
                    haversine(loc1.lat,loc1.lng,loc2.lat,loc2.lng)*111.045 as distance
                FROM 
                    ow_googlelocation_data as loc2,
                    ow_base_user as u1 INNER JOIN ow_base_user u2
                        ON u1.id = u2.id
                    LEFT JOIN ow_base_user_suspend s
                        ON s.userId = u1.id
                    INNER JOIN ow_googlelocation_data as loc1 
                        ON loc1.entityId = u1.id
                WHERE
                    s.id IS NULL AND
                    u1.emailVerify = 1 AND
                    loc1.entityType='user' AND
                    loc2.entityType = 'user' AND
                    u1.id != loc2.entityId AND
                    loc2.entityId = ".$userId."
                HAVING distance < ".$distance."
                ORDER BY 
                    distance ASC
                LIMIT 100 ";
        
        return OW::getDbo()->queryForObjectList($query, "BOL_User", array($first, $count));
    }
    
    public function getUserData($userListData) {
        
        $loggedInUid = OW::getUser()->getId();
        
//        for($i=0; $i<count(userListData); $i++) {
//            if ($userListData[$i]->avatar_url == null){
//               $userListData[$i]->avatar_url = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
//            }
//        }
//      
        foreach ($userListData as $key => $user) {
            
            $userId = $user->id;
            
            if (!isset($user->avatar_url) || is_null($user->avatar_url)){
               $user->avatar_url = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
                $user->avatar_url_default = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
            }
            
            $questions = BOL_QuestionService::getInstance()->getQuestionData(array($userId), array('realname', 'googlemap_location', 'birthdate', 'sex'));
            $user->name = $questions[$userId]['realname'];
            if(isset($questions[$userId]['googlemap_location']))
            {
                $user->location =  $questions[$userId]['googlemap_location'];
                $user->latitude = isset($user->location["latitude"])?$user->location["latitude"]:"" ;
                $user->longitude = isset($user->location["longitude"])?$user->location["longitude"]:"" ;
                $user->address = isset($user->location["address"])?$user->location["address"]:"" ; 
            }
            
            $ageStr = "";
            if ( !empty($questions[$userId]['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($questions[$userId]['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);
                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
                $ageStr = $age . ' ' . OW::getLanguage()->text('base', 'questions_age_year_old');
            }
            
            $user->age = $ageStr;

            $sexValue = '';
            if ( !empty($questions[$userId]['sex']) )
            {
                $sex = $questions[$userId]['sex'];

                for ( $i = 0; $i < 31; $i++ )
                {
                    $val = pow(2, $i);
                    if ( (int) $sex & $val )
                    {
                        $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                    }
                }

                if ( !empty($sexValue) )
                {
                    $sexValue = substr($sexValue, 0, -2);
                }
            }
            
            $user->sex = $sexValue;
            
            $user->avatar_url = BOL_AvatarService::getInstance()->getAvatarUrl($userId, 2);
            $user->userId = $userId;
            
            if ($userId == $loggedInUid)
            {
                $user->isLoggedInUser = true;
            } else {
                
                $user->isLoggedInUser = false;
            }
        
        }
        
        return $userListData;
    }
    
    
    /*** FRIENDS BRIDGE ***/
    public function friendRequest( $friendId, $command )
    {
        $error = null;
        $msg = null;
        
        if ( !OW::getUser()->isAuthenticated() )
        {
           $error = "User is not authenticated";
        }
        
        else {

            $requesterId = OW::getUser()->getId();

            $userId = $friendId;

            if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
            {
                $error = "User is blocked";
            }
            
            elseif (!OW::getUser()->isAuthorized('friends', 'add_friend'))
            {
                $error = "User is not authorized to add friends";
            }

            else 
            {
                //We checked everything, now we can use a switch for the command
                $service = FRIENDS_BOL_Service::getInstance();
                
                switch($command)
                {
                    case 'add':
                        if ( $service->findFriendship($requesterId, $userId) === null )
                        {
                            $service->request($requesterId, $userId);

                            $service->onRequest($requesterId, $userId);

                            $msg = OW::getLanguage()->text('friends', 'feedback_request_was_sent');
                        }
                        else
                        {
                            $msg = OW::getLanguage()->text('friends', 'feedback_request_already_sent_error_message');
                        }
                        break;
                    //cancel request if friendship is pendinf or remove friend is friendship is active
                    case 'cancel':
                        $event = new OW_Event('friends.cancelled', array(
                        'senderId' => $requesterId, //check if order ok
                        'recipientId' => $userId
                        ));
                        OW::getEventManager()->trigger($event);
                        $msg = OW::getLanguage()->text('friends', 'feedback_cancelled');
                        break;
                    
                    case 'activate': 
                        
                        $service->activate($userId, $requesterId);
                        $msg = OW::getLanguage()->text('friends', 'new_friend_added');
                        break;
                
                    case 'accept':
                        $userId = (int) OW::getUser()->getId();
                        $requesterId = $friendId;
                        $service = FRIENDS_BOL_Service::getInstance();
                        $frendshipDto = $service->accept($userId, $requesterId);
                        if ( !empty($frendshipDto) )
                        {
                            $service->onAccept($userId, $requesterId, $frendshipDto);
                        }
                        $msg = OW::getLanguage()->text('friends', 'feedback_request_accepted');
                        break;

                    case 'ignore':
                        $userId = (int) OW::getUser()->getId();
                        $requesterId = $friendId;
                        $service->ignore($requesterId, $userId);
                        $msg = OW::getLanguage()->text('friends', 'feedback_request_ignored');
                        break;
                }

            }
        }
        
        //Return
        $out = array ("error" => $error, "msg" => $msg);
        return $out;
    }
    
    
}

