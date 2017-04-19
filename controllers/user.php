<?php

/**
 * Description of User Ctrl Class
 *
 * @author Kalianey
 */

class OWAPI_CTRL_User extends OWAPI_CLASS_ApiController
{
    function profile($params)
    {

        $user = OW::getUser();
        $userId = $user->getId();
        $isLoggedInUser = true;

        
        if ( isset($params['userId']) && !is_null($params['userId']   ) )
        {
            $userId = $params['userId'];
            $isLoggedInUser = false;    
        }
        
        //get userInfo
        $userInfo = $this->bridgeService->getUserInfo($userId);
        
        $data = array(
            'userId' => $userId,
            'name' => $userInfo['name'],
            'avatar_url' => $userInfo['avatar_url'],
            'cover_url' => $userInfo['cover_url'],
            'isLoggedInUser' => $isLoggedInUser,
            'age' => $userInfo['age'],
            'sex' => $userInfo['sex'],
            'email' => $userInfo['email'],
            'address' => $userInfo['address']
        );
        
        if (is_null($user)){
            $this->error('authentication failed');
        } 
        else {
        
            $this->success($data);
        }

    }
    
    
    function extra($params){
        
        $user = OW::getUser();
        if (is_null($user))
        {
            $this->error('authentication failed');
        }
        $userId = $user->getId();
        
        if ( isset($params['userId']) && !is_null($params['userId']   ) )
        {
            $userId = $params['userId'];   
        }
        
        $userPhotos = $this->bridgeService->findUserPhotos($userId, 0, 1000);
        
        $userFriends = $this->bridgeService->findUserFriends($userId, 0, 1000);
        
        $questions = $this->bridgeService->getUserViewQuestions($userId); 
        
        $data = array(
            'photos' => $userPhotos,
            'friends' => $userFriends,
            'questions' => $questions
        );

        $this->success($data);
        
    }
    
    function updateProfile(){
        
        $user = OW::getUser();
        if (is_null($user))
        {
            $this->error('authentication failed');
        }
        else {
            $msg = array();
            $userId = $user->getId();
            $oldPassword = $_POST['oldPassword'];
            $password = $_POST['password'];
            $email = $_POST['email'];
            $realname = $_POST['realname'];
            $defaultLang = 'fr';
            
            $lang = array (
                'en' => array (
                    'pass' => 'Incorrect password, please try again.',
                    'updated' => 'Modification effectuée'
                ),
                'fr' => array (
                    'errorPass' => 'Mot de passe incorrect, merci de réessayer.',
                    'updated' => 'Modification effectuée'
                )
            );
            
            if ( $password != "" ) {
                //works but can set <script>lala</script> as a password, problem?
                $result = OW::getUser()->authenticate(new BASE_CLASS_StandardAuth($user->getEmail(), $oldPassword));

                if ( $result->isValid() )
                {
                    BOL_UserService::getInstance()->updatePassword( $userId, $password );
                    $msg []= "Password";
                } else {
                    $this->error($lang[$defaultLang]['errorPass']);
                }
            }
            if ($email != ""){
                
                BOL_UserService::getInstance()->updateEmail( $userId, $email );
                $msg []= "Email";
            }
            if ($realname != ""){
                //TODO: check if realname is valid
                BOL_QuestionService::getInstance()->saveQuestionsData(array( 'realname' => $realname ), $userId);
                $msg []= "Realname";

            } 
            if (count($msg) == 0) {
                $msg = "Nothing changed.";
                $this->error($msg);
            }
            
            $msg = implode(', ',$msg);
            $this->success($lang[$defaultLang]['updated']);
        }
    }
    
    
    function updateAvatar()
    {
        
        $user = OW::getUser();
        if (is_null($user))
        {
            $this->error('authentication failed');
        }
        else {
            if ( isset($_FILES['file']) )
            {
                $file = $_FILES['file'];

                $lang = OW::getLanguage();

                if ( !UTIL_File::validateImage($file['name']) )
                {
                    $this->error($lang->text('base', 'not_valid_image'));
                }

                $message = BOL_FileService::getInstance()->getUploadErrorMessage($_FILES['file']['error']);

                if ( !empty($message) )
                {
                    $this->error($message);
                }

                $filesize = OW::getConfig()->getValue('base', 'avatar_max_upload_size');

                if ( $filesize*1024*1024 < $_FILES['file']['size'] )
                {
                    $message = OW::getLanguage()->text('base', 'upload_file_max_upload_filesize_error');
                    $this->error($message);
                }

                $avatarService = BOL_AvatarService::getInstance();

                $key = $avatarService->getAvatarChangeSessionKey();

                $result = $avatarService->setUserAvatar($user->getId(), $file['tmp_name']);
                
                if ($result) 
                {
                    $urlBig = $avatarService->getAvatarUrl($user->getId(), 1);

                    $this->success($urlBig);
                 }
                             
            }

            $this->error("false");
        }
    }
    
    
    
    function all($params)
    {
        $this->isAuth();    
    
        $first = (int) $params['first'];
        $count = 21;
        $userListData = $this->userDao->findList($first, $count); 

        $list = $this->bridgeService->getUserData($userListData);
        $this->success($list); //It actually return everything, which is so weird!
    }
    
    
    
    function isFriend($params){
        $userId = OW::getUser()->getId();
        $friend = FRIENDS_BOL_Service::getInstance()->findFriendship($userId, (int) $params['friendId']);
        $isFriend = $friend->status;
        
         if (is_null($friend)){
         
            $this->error("No friendship found");
            
        } else {
            
            $out = array (
                'isFriend' => $isFriend,
                'friendStatus' => $friend
            );
            
            $this->success($out);
        }
    }
    
    
    function friend($params){
        
        $friendId = (int) $params['friendId'];
        $command = $params['command'];
        $out = $this->bridgeService->friendRequest($friendId, $command);
        $error = $out['error'];
        $msg =  $out['msg'];
        
        if (!is_null($error)){

            $this->error($error);

        } else {

            $result = array (
                'error' => $error,
                'msg' => $msg
            );

            $this->success($result);
        }
        
    }
    
    
    function friendList()
    {
        $count = 1000;
        $userId = OW::getUser()->getId();
        
        if (is_null(OW::getUser()))
        {
            $this->error('authentication failed');
        } 
        else {

            $service = FRIENDS_BOL_Service::getInstance();
            
            $friendIdList = $service->findFriendIdList($userId, 0, $count );
            
            $userListData = MAILBOX_BOL_ConversationService::getInstance()->getUserInfoForUserIdList( $friendIdList);
            
            foreach($userListData as $user) {
                $userid = $user['opponentId'];
                $userInfo = $this->bridgeService->getUserInfo($userid);
                $userListData["".$userid.""]['coverUrl'] = $userInfo['cover_url'];
                $userListData["".$userid.""]['age'] = $userInfo['age'];
                $userListData["".$userid.""]['sex'] = $userInfo['sex'];
                $userListData["".$userid.""]['address'] = $userInfo['address'];
            }
              
            $this->success($userListData); 
        
        }
    }
    
    
    function friendRequestList(){
        $userId = OW::getUser()->getId();
        $first = 0;
        $count = 50;
        $service = FRIENDS_BOL_Service::getInstance();
        $userService = BOL_UserService::getInstance();
        $idList = $service->findFriendIdList($userId, $first, $count, 'got-requests');
        $list = $userService->findUserListByIdList($idList);
        
        for($i = 0; $i < count($list); $i++) {
            $userId = $list[$i]->id;
            //get userInfo
            $userInfo = $this->bridgeService->getUserInfo($userId);
            $list[$i]->name = $userInfo['name'];
            $list[$i]->avatar_url = $userInfo['avatar_url'];
            $list[$i]->cover_url = $userInfo['cover_url'];
            $list[$i]->age = $userInfo['age'];
            $list[$i]->sex = $userInfo['sex'];
            $list[$i]->email = $userInfo['email'];
            $list[$i]->address = $userInfo['address'];
        } 
        
        if (is_null($userId)){

            $this->error("An error occurred while retrieving the friends requests.");

        } 
        elseif (empty($idList)){
            $this->error("No friend request.");
        }
        else {
            $result = array (
                'friendsRequest' => $list
            );

            $this->success($result);
        }
        
    }
    
    
    
    function isFavorite($params){
        $userId = OW::getUser()->getId();
        $favoriteId = (int) $params['favoriteId'];
        $favorite = OCSFAVORITES_BOL_Service::getInstance()->isFavorite($userId, $favoriteId);
        
        if (is_null($favorite)){
         
            $this->error("An error occurred while checking favorite status");
            
        } else {
            
            $out = array (
                'isFavorite' => $favorite
            );
            
            $this->success($out);
        }
        
    }
    
    function favorite($params){
        
        $service = OCSFAVORITES_BOL_Service::getInstance();
        $lang = OW::getLanguage();

        $userId = OW::getUser()->getId();
        $favoriteId = (int) $params['favoriteId'];
        $command = $params['command'];
        $result = false;
        $msg = null;

        $favorite = $service->isFavorite($userId, $favoriteId);

        if ( !$favorite && !OW::getUser()->isAuthorized('ocsfavorites', 'add_to_favorites') )
        {

            $result = false;
            $msg = 'User is not authorized to use favorites';
        }
        else  {
            $user = BOL_UserService::getInstance()->findUserById($favoriteId);
            
            if ( !$user )
            {
                $result = false;
                $msg = 'User is null';
            }

            else if ( $favorite && $command == "add-favorite" || !$favorite && $command == "remove-favorite" )
            {
                $result = false;
                $msg = 'The command is not valid in favorites';
            }
            
            else {
                switch ( $command )
                {
                    case "add":
                        if ($favorite) {
                            $result = false;
                            $msg = 'User is already in favorites';
                        } else {
                        $service->addFavorite($userId, $favoriteId);
                        BOL_AuthorizationService::getInstance()->trackActionForUser($userId, 'ocsfavorites', 'add_to_favorites');
                        $result = true;
                        $msg = $lang->text('ocsfavorites', 'favorite_added');
                        }
                        break;

                    case "remove":
                        if (!$favorite) {
                            $result = false;
                            $msg = 'User is not yet in favorites';
                        } else {
                            $service->deleteFavorite($userId, $favoriteId);
                            $result = true;
                            $msg = $lang->text('ocsfavorites', 'favorite_removed');
                        }
                        break;
                }
            }
        
        }
        
        //Return
        if (!$result){
         
            $this->error($msg);
            
        } else {
            
            $out = array (
                'msg' => $msg
            );
            
            $this->success($out);
        }
    }
    
    
    
    public function favoriteList()
    {
        $count = 100;
        $userId = OW::getUser()->getId();

        $service = OCSFAVORITES_BOL_Service::getInstance();
        $lang = OW::getLanguage();
        
        $multiple = OW::getConfig()->getValue('ocsfavorites', 'can_view') && OW::getUser()->isAuthorized('ocsfavorites', 'view_users');

        $lists = array();
        $resultList = array();
        
        $lists['my'] = $service->findFavoritesForUser($userId, 1, $count);

        if ( $multiple )
        {
            $lists['me'] = $service->findUsersWhoAddedUserAsFavorite($userId, 1, $count);
            $lists['mutual'] = $service->findMutualFavorites($userId, 1, $count);
        }
        
        $myIdList = $this->getIds($lists['my'], 'favoriteId');
        foreach ($myIdList as $key=>$value){
            $myIdList[$key] = $this->bridgeService->getUserInfo($value);
        }

        $resultList['my'] = array(
            'menu-label' => $lang->text('ocsfavorites', 'my'),
            'userIds' => $myIdList
        );

        if ( $multiple )
        {
            if ( $lists['me'] )
            { 
                $addedMeIdList = $this->getIds($lists['me'], 'userId');
                foreach ($addedMeIdList as $key=>$value){
                    $addedMeIdList[$key] = $this->bridgeService->getUserInfo($value);
                }

                $resultList['me'] = array(
                    'menu-label' => $lang->text('ocsfavorites', 'who_added_me'),
                    'userIds' => $addedMeIdList
                );
            }

            if ( $lists['mutual'] )
            {
                
                $mutualIdList = $this->getIds($lists['mutual'], 'userId');
                foreach ($mutualIdList as $key=>$value){
                    $mutualIdList[$key] = $this->bridgeService->getUserInfo($value);
                }
                $resultList['mutual'] = array(
                    'menu-label' => $lang->text('ocsfavorites', 'mutual'),
                    'userIds' => $mutualIdList
                );
            }
        }
  
        if ($userId == 0)
        {
            $this->error('authentication failed');
        } 
        else {
            $this->success($resultList);
        }
    }
    
    
    private function getIds( $favorites, $name )
    {
        $resultArray = array();

        if ( $favorites )
        {
            foreach ( $favorites as $f )
            {
                $resultArray[] = $f->$name;
            }
        }

        return $resultArray;
    }
    
    
    
    //BLOCK
    function isBlocked($params){
        $userId = OW::getUser()->getId();
        $blockedUserId = (int) $params['userId'];
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->error("Not authenticated user");
        }
         else {
            
            $isBlocked = BOL_UserService::getInstance()->isBlocked($blockedUserId, $userId);
            $this->success($isBlocked);
        }
        
    }
    
    
    function block($params){
        
        $service = BOL_UserService::getInstance();
        $command = $params['command'];
        $result = false;
        $msg = null;

        if ( !OW::getUser()->isAuthenticated() )
        {

            $result = false;
            $msg = 'User is not authenticated authorized';
        }
        else  {
            
            if ( $command == 'block' )
            {
                $service->block($params["userId"]);
                $result = true;
                $msg = 'User blocked';
            }
            
            else 
            {
                $service->unblock($params["userId"]);
                $result = true;
                $msg = 'User unblocked';
            }
        
        }
        
        //Return
        if (!$result){
         
            $this->error($msg);
            
        } else {
            
            $this->success($msg);
        }
    }
    
    
    //FLAG  
    function flag($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->error("Not authenticated user");
        }
        else {
            $entityType = 'user_join';
            $entityId = $params["userId"];
            $userId = OW::getUser()->getId();

            if ( $entityId == $userId )
            {
                $this->error("You cannot flag your own content");
            }

            else {
                $service = BOL_FlagService::getInstance();
                $service->addFlag($entityType, $entityId, $params['reason'], $userId);
                $this->success('Content successfully flagged');
            }
        }
    }
    
    
    //GEOLOC FUNCTIONS 
    
    function near($params) 
    {
        $first = 0;
        $count = 144;
        $loggedInUid = OW::getUser()->getId();
        
        if ( isset($params['distance']) && !is_null($params['distance'] ) )
        {
            $distance = $params['distance'];  
        } else {
            $distance = 3000;
        }
        
        $userListData = $this->bridgeService->findUserListByDistance($first, $count, $distance); 
        
        $this->bridgeService->getUserData($userListData);

        $this->success($userListData);
    }
    
    
    //func updateLocation with reverse geoloc to adapt to googlemaplocation plugin
    function updateLocation(){
        
        $user = OW::getUser();
        if (is_null($user))
        {
            $this->error('authentication failed');
        }
        $userId = $user->getId();
        //print_r($userId);
        OW::getLogger()->addEntry("Here is the userId which might trigger an error: ". $userId);
        
        //json received, now need to add country before saving
        if (isset($_POST["json"]))
        {
            $json_encoded = $_POST["json"];
            $json = json_decode($json_encoded, true);

            $location = $this->locationService->findByUserId($userId);

            if ( empty($location) )
            {
                $location = new GOOGLELOCATION_BOL_Location();
            }
            
            $countryCode = "";
            $city = "";
            $country = "";
            if ( !empty($json['address_components']) )
            {
                foreach ( $json['address_components'] as $component )
                {
                    
                    if ( !empty($component['types']) && is_array($component['types']))
                    {
                        if ( in_array('country', $component['types']) )
                        {
                            $countryCode = !empty($component['short_name']) ? $component['short_name'] : "";
                            $country = !empty($component['long_name']) ? $component['long_name'] : "";
                        }
                        if( in_array('locality', $component['types']) )
                        {
                            $city = !empty($component['short_name']) ? $component['short_name'] : "";
                        }
                    }
                }
            }
            
            $address_components = json_encode(array('address_components'=> $json['address_components']));
            $coordinates = $json['geometry']['location'];
            $viewport = $json['geometry']['viewport'];

            $location->entityId = (int) $userId;
            OW::getLogger()->addEntry("Integrity constraint violation: 1062 Duplicate entry '0-user' for key 'entityId ---> here is the userID: ". $userId);
            $location->countryCode = $countryCode;
            
            //TODO: remove the complete address and leave just the city
            $location->address = !empty($city) ? $city.', '.$country : $json['formatted_address']; //
            $location->lat = (float) $coordinates['lat']; //could remove the last digit with range() to decrease the precision
            $location->lng = (float) $coordinates['lng']; 
            $location->northEastLat = (float) $viewport['northeast']['lat']; 
            $location->northEastLng = (float) $viewport['northeast']['lng'];
            $location->southWestLat = (float) $viewport['southwest']['lat'];
            $location->southWestLng = (float) $viewport['southwest']['lng'];
            $location->json = !empty($address_components) ? $address_components : "";
            $location->entityType = GOOGLELOCATION_BOL_LocationDao::ENTITY_TYPE_USER;

            $this->locationService->save( $location );
            
            $this->success();
        }
        else 
        {
            $this->error("No json sent");
        }
        
    }
    

    //func near() with no param near last location, send a range
//    function near($lat, $lng, $distance)
//    {
//        $lat = 45.769553;
//        $lng = 4.832137;
//        $distance = 1000; //distance goes funny after 7000km
//        
//        //we get a new rectangle according to the distance given around the coordinates
//        $ne = $this->locationService->getNewCoordinates( $lat, $lng, 45, $distance );
//        $sw = $this->locationService->getNewCoordinates( $lat, $lng, 225, $distance );
//
//        
//        $tableName = $this->locationDao->getTableName();
//        $userType = GOOGLELOCATION_BOL_LocationDao::ENTITY_TYPE_USER;
//
//        //we look for user within this viewport
//        $sql = "SELECT entityId FROM $tableName
//                WHERE 
//                entityType = '$userType' AND
//                lat BETWEEN {$sw['lat']} AND {$ne['lat']} AND
//                lng BETWEEN {$sw['lng']} AND {$ne['lng']} ";
//        
//        $idList = OW::getDbo()->queryForList($sql);
//
//        $this->success($idList);
//        
//    }
    
    
    function delete(){
          
        if ( !OW::getUser()->isAuthenticated() )
        {
            $msg = 'User is not authenticated authorized';
            $this->error($msg);
        } 
        else {
            $userId = OW::getUser()->getId();
            $result = BOL_UserService::getInstance()->deleteUser($userId);
            if($result) {
                //Delete the connected fb/google remote auth account if exist
                $remote_service = BOL_RemoteAuthService::getInstance();
                $remote_service->deleteByUserId($userId);
            }
            $this->success($result);
        }
        
    }
    
}