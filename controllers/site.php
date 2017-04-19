<?php

/* 
 *  OWAPI Site Controller
 *
 * @author Kalianey <kalianey@gmail.com>
 * @package ow_plugins.owapi.controllers
 * @since 1.0
 */

require_once OW_DIR_PLUGIN.'glconnect'.DS.'lib'.DS.'httpcurl.php';

class OWAPI_CTRL_Site extends OWAPI_CLASS_ApiController
{
    private $fbScope = 'email,public_profile';
    private $httpcurl;
    public $client_id;
    public $userinfo = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token='; 
    public $tokeninfo = 'https://www.googleapis.com/oauth2/v2/tokeninfo?access_token=';
    
    
    public function __construct ()
    {
        $this->client_id = 'YOUR_CLIENT_ID';
        $this->httpcurl = new HTTPCurl();
        $this->httpcurl->setUserAgent ('(Google connect/Oxwall)');
        $this->httpcurl->setSSLVerify (false);
        $this->httpcurl->setCache (false);
        $this->httpcurl->setHeaderBody (false);
    }
    
    public function glConnect()
    {
        $token = $_POST['token'];
        //echo $token;
        unlink('site.error.log');
        
        $language = OW::getLanguage();
        
        $userinfo = $this->glGetUserInfo($token);
        $tokeninfo = $this->glGetTokenInfo($token);
        if(is_null($userinfo) || is_null($tokeninfo))
        {
            $this->error('Token not found');
        }
        $allinfo = array_merge($userinfo,$tokeninfo);
        
        
        $msg = print_r($allinfo,true);
        file_put_contents('site.error.log', $msg, FILE_APPEND);
        
        $result = $this->glLogin($allinfo);
        
        $msg = print_r($result,true);
        file_put_contents('site.error.log', $msg, FILE_APPEND);
        
         //We return the result
        if (!$result['isAuthenticated']){
            $this->error($result['error']);
        } else {
            $this->success($result);
        }
        
    }

    public function glGetUserInfo($token)
    {
        $this->httpcurl->setUrl($this->userinfo.$token);
        $this->httpcurl->setPostMethod (false);
        $this->httpcurl->execute();
        return json_decode ($this->httpcurl->content,true);
    }
    
    public function glGetTokenInfo($token)
    {
        $this->httpcurl->setUrl($this->tokeninfo.$token);
        $this->httpcurl->setPostMethod (false);
        $this->httpcurl->execute();
        return json_decode ($this->httpcurl->content,true);
    }


    public function glLogin($params)
    {
        $language = OW::getLanguage();
        $isAuthenticated = false;
        $userId = null;
        $error = null;
        $msg = null;
        
        if (!$params['issued_to'] == $this->client_id) {
            //print_r($params);
            $error = "the aud claim DO NOT contains one of your app's client IDs.";
        }
        else 
        {
            
            //TODO: check if email is there
            $email = $params['email'];

            // Register or login
            $user = BOL_UserService::getInstance()->findByEmail($email);

            if (!empty($user)) {
                // LOGIN
                OW::getUser()->login($user->id);
                $msg = $language->text('glconnect', 'login_success_msg');
                $isAuthenticated = true; 
                $userId = $user->id;;
            } 
            else {
                //REGISTER
                $authAdapter = new GLCONNECT_CLASS_AuthAdapter($params['email']);
                $username = $params['name'];
                $username = $this->removeAccents($username);
                $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
                $username = strtolower($username);

                $usernameExist = BOL_UserDao::getInstance()->findByUserName($username);

                if (!is_null($usernameExist)) {
                    $username = $username . $params['id'];
                }

                $password = uniqid();
                file_put_contents('site.error.log', "\n\n ($username)", FILE_APPEND);
                
                try { 
                    $user = BOL_UserService::getInstance()->createUser($username, $password, $params['email'], null, $params['verified_email']);
                    $user->username = $username; //mods
                } 
                catch (Exception $e) 
                {
                    switch ($e->getCode()) {
                        case BOL_UserService::CREATE_USER_DUPLICATE_EMAIL:
                            $error = $language->text('glconnect', 'join_dublicate_email_msg');
                            break;
                        case BOL_UserService::CREATE_USER_INVALID_USERNAME:
                            $error = $language->text('glconnect', 'join_incorrect_username');
                            break;
                        default:
                            $error = $language->text('glconnect', 'join_incomplete');
                            break;
                    }
                    $out = array (
                        'error' => $error,
                        'userId' => 0,
                        'msg' => '',
                        'isAuthenticated' => false
                    );

                    return $out;
                    
                } 
                //END TRY-CATCH

                BOL_UserService::getInstance()->saveOrUpdate($user);
                BOL_QuestionService::getInstance()->saveQuestionsData(array('realname' => $params['name']), $user->id);
                BOL_AvatarService::getInstance()->setUserAvatar($user->id, $params['picture']);

                switch ($params['gender']) {
                    case 'male' : BOL_QuestionService::getInstance()->saveQuestionsData(array('sex' => 1), $user->id);
                        break;
                    case 'female' : BOL_QuestionService::getInstance()->saveQuestionsData(array('sex' => 2), $user->id);
                        break;
                }
                $authAdapter->register($user->id);
                $authResult = OW_Auth::getInstance()->authenticate($authAdapter);

                if ($authResult->isValid()) {
                    $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('method' => 'google', 'userId' => $user->id));
                    OW::getEventManager()->trigger($event);
                    $msg = $language->text('glconnect', 'join_success_msg');
                    $userId = $user->id;
                    $isAuthenticated = true;
                } 
                else {
                    $error = $language->text('glconnect', 'join_failure_msg');
                    $isAuthenticated = false;
                }

            }
        
        }
        $out = array (
            'error' => $error,
            'userId' => $userId,
            'msg' => $msg,
            'isAuthenticated' => $isAuthenticated
        );
        
        return $out;
        
    }
    
    
    
    // FACEBOOK CONNECT
     function fbConnect()
    {
       $token = $_POST['token'];
       $isAuthenticated = false;
       $userId = null;
       $error = null;
        
       $fbUser = $this->fbRequireUser($token);
       
       $authAdapter = new FBCONNECT_CLASS_AuthAdapter($fbUser);
       
       // Login the user if already registered
        if ( $authAdapter->isRegistered() )
        {
            
            $authResult = OW::getUser()->authenticate($authAdapter);

            if ( $authResult->isValid() )
            {
                //echo 'User is valid!';
                $isAuthenticated = true;
                $userId = $authResult->getUserId();
                
            }
        }
         
        //if the user is not found, we try to find him by email
        if (!$isAuthenticated) {
        
            $questions = FBCONNECT_BOL_Service::getInstance()->requestQuestionValueList($fbUser);

            if ( empty($questions["email"]) || empty($questions["username"]) )
            {

            }

            $username = $questions['username'];
            $password = uniqid();

            $userByEmail = BOL_UserService::getInstance()->findByEmail($questions['email']);

            //If userByEmail return a result, we can authenticate the user
            if ( $userByEmail !== null )
            {
                OW::getUser()->login($userByEmail->id);
                $userId = $userByEmail->id;
                $isAuthenticated = true;
            }
            
            //If the user is still not found, we register him
            else {
            
                $validUsername = UTIL_Validator::isUserNameValid($username);
                $username = $validUsername ? $username : uniqid("user_");

                try
                {
                    $user = BOL_UserService::getInstance()->createUser($username, $password, $questions['email'], null, true);

                    if ( !$validUsername )
                    {
                        $user->username = "user_" . $user->id;

                        BOL_UserService::getInstance()->saveOrUpdate($user);
                    }

                    unset($questions['username']);
                    unset($questions['email']);
                }
                catch ( Exception $e )
                {
                    switch ( $e->getCode() )
                    {
                        case BOL_UserService::CREATE_USER_DUPLICATE_EMAIL:
                            $error = $language->text('fbconnect', 'join_dublicate_email_msg');
                            break;

                        case BOL_UserService::CREATE_USER_INVALID_USERNAME:
                            $error = $language->text('fbconnect', 'join_incorrect_username');
                            break;

                        default:
                            $error = $language->text('fbconnect', 'join_incomplete');
                    }
                }

                if ( !empty($questions['picture_big']) )
                {
                    BOL_AvatarService::getInstance()->setUserAvatar($user->id, $questions['picture_big']);

                    unset($questions['picture_small']);
                    unset($questions['picture_medium']);
                    unset($questions['picture_big']);
                }

                BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($questions), $user->id);

                $authAdapter->register($user->id);

                $authResult = OW_Auth::getInstance()->authenticate($authAdapter);
                if ( $authResult->isValid() )
                {
                    $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array(
                        'method' => 'facebook',
                        'userId' => $user->id,
                        'params' => $_GET
                    ));
                    OW::getEventManager()->trigger($event);

                    $isAuthenticated = true;

                }
                else
                {
                    $error = $language->text('fbconnect', 'join_failure_msg');
                }

            }
            
        }

        //We return the result
        if (!$isAuthenticated){
         
            $this->error($error);
            
        } else {
            
            $result = array (
                'error' => $error,
                'userId' => $userId,
                '$isAuthenticated' => $isAuthenticated
            );
            
            $this->success($result);
        }
    }
    

    public function fbRequireUser($token)
    {
        $facebook = $this->getFaceBook($token);
        $user = $facebook->getUser();
        if ( empty($user) )
        {
            $loginUrl = $facebook->getLoginUrl(array(
                'scope' => $this->$fbScope
            ));

            throw new RedirectException($loginUrl);
        }

        return $user;
    }
    
    public function getFaceBook($token)
    {

        $params = array(
            'appId' => "YOUR_APP_ID",
            'secret' => "YOUR_APP_SECRET"
        );

        $faceBook = new Facebook($params);
        $faceBook->setAccessToken($token);
        return $faceBook;
    }
    
    
      
     private function removeAccents($str)
    {
      $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
      $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
      return str_replace($a, $b, $str);
    }
    
}