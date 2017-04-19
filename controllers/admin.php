<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class OWAPI_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function getMenu()
    {
        $menu = new BASE_CMP_ContentMenu();
        
        $item = new BASE_MenuItem();
        $item->setLabel(OW::getLanguage()->text('owapi', 'general_settings'));
        $item->setIconClass('ow_ic_dashboard');
        $item->setKey('1');
        $item->setUrl(OW::getRouter()->urlForRoute('owapi.admin'));
        $item->setOrder(1);
        $menu->addElement($item);
        
        $item = new BASE_MenuItem();
        $item->setLabel(OW::getLanguage()->text('owapi', 'btn_push_ios'));
        $item->setIconClass('ow_ic_files');
        $item->setKey('2');
        $item->setUrl(OW::getRouter()->urlForRoute('owapi.admin.push.ios'));
        $item->setOrder(2);
        $menu->addElement($item);
        
        $item = new BASE_MenuItem();
        $item->setLabel(OW::getLanguage()->text('owapi', 'btn_push_android'));
        $item->setIconClass('ow_ic_files');
        $item->setKey('3');
        $item->setUrl(OW::getRouter()->urlForRoute('owapi.admin.push.android'));
        $item->setOrder(3);
        $menu->addElement($item);

        return $menu;
    }
    
    public function index()
    {
        $this->addComponent('menu', $this->getMenu());
        $push = OWAPI_CLASS_PushIos::getInstance();

        $userId = OW::getUser()->getId();
        $messagesUnread = MAILBOX_BOL_MessageDao::getInstance()->findUnreadConversations($userId);
        $badge = count($messagesUnread);
        
        echo '<pre>';
        var_dump($badge);
        print_r($messagesUnread);
        echo '</pre>';
    }
    
    public function pushIos()
    {
        $lang = OW::getLanguage();
        $config = OW::getConfig();
        
        $this->addComponent('menu', $this->getMenu());
        
        $cert_status = 'no';
        
        $form = new OWAPI_AdminPushIosForm();
        if(OW::getRequest()->isPost() && $form->isValid($_POST))
        {
            $form->setValues($_POST);
            if(!$form->process())
            {
                $cert_status = 'error';
            }
        }
        $this->addForm($form);
        
        $cert_path = $config->getValue('owapi', 'ios_push_cert_path');
        if($cert_path != '')
        {
            $cert_status = 'ok';
        }
        
        $this->assign('cert_status', $cert_status);
        
    }
    
    public function pushAndroid()
    {
        $this->addComponent('menu', $this->getMenu());
    }
    
}


class OWAPI_AdminPushIosForm extends Form
{    
    var $cert_path;
    function __construct() {
        parent::__construct('push_ios');
        $lang = OW::getLanguage();
        
        
        $config = OW::getConfig()->getInstance();
        
        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        $this->setMethod(Form::METHOD_POST);
        
        $enabled = new RadioField('enabled');
        $enabled->setOptions(array(
            '1'=>$lang->text('base', 'yes'),
            '0'=>$lang->text('base', 'no'),
        ));
        $enabled->setValue($config->getValue('owapi', 'ios_push_is_enable'));
        $enabled->setLabel($lang->text('owapi', 'ios_push_notification_enabled'));
        
        
        $sandbox = new RadioField('sandbox');
        $sandbox->setOptions(array(
            '1'=>$lang->text('base', 'yes'),
            '0'=>$lang->text('base', 'no'),
        ));
        $sandbox->setValue($config->getValue('owapi', 'ios_push_is_sandbox'));
        $sandbox->setLabel($lang->text('owapi', 'is_sandbox'));
        
        
        $this->cert_path = new FileField('cert_path');
        $this->cert_path->setLabel($lang->text('owapi', 'lbl_cert_path'));
        
        
        
        $submit = new Submit('submit');
        $submit->setValue($lang->text('base', 'save'));
        
        
        
        $this->addElement($enabled);
        $this->addElement($sandbox);
        $this->addElement($this->cert_path);
        
        
        $this->addElement($submit);
    }
    
    function process()
    {
        $config = OW::getConfig();
        $pathConfig = OW::getPluginManager()->getPlugin('owapi')->getPluginFilesDir().'config/';
        $pathLib = OW::getPluginManager()->getPlugin('owapi')->getRootDir().'lib/';
        
        $data = $this->getValues();
        $enabled = $data['enabled'];
        $sandbox = $data['sandbox'];

        $config->saveConfig('owapi', 'ios_push_is_enable',$data['enabled']);
        $config->saveConfig('owapi', 'ios_push_is_sandbox',$data['sandbox']);
        
        if(isset($_FILES['cert_path']))
        {
            $file = $_FILES['cert_path'];
            if($file['type']=='application/x-x509-ca-cert')
            {
                $name = $file['name'];
                $certPath = $pathConfig . $name;
                move_uploaded_file($file['tmp_name'], $certPath);
                chmod($certPath, 777);
                
                $push = new OWAPI_CLASS_PushIos();
                if($push->connect($sandbox, $certPath))
                {
                    $config->saveConfig('owapi', 'ios_push_cert_path',$certPath);
                }
                else
                {
                    unlink($certPath);
                    $config->saveConfig('owapi', 'ios_push_cert_path','');
                    return false;
                }
            }
        }
        
        return true;
        
        
    }
    
}