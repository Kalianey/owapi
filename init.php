<?php

OW::getRouter()->addRoute(new OW_Route('owapi.site', '/owapi/site', 'OWAPI_CTRL_Site', 'index'));

/*** User ***/
OW::getRouter()->addRoute(new OW_Route('owapi.user.profile', '/owapi/user/profile/:userId', 'OWAPI_CTRL_User', 'profile', array('userId'=>array(OW_Route::PARAM_OPTION_DEFAULT_VALUE => null))));
OW::getRouter()->addRoute(new OW_Route('owapi.user.profile.extra', '/owapi/user/profile/extra/:userId', 'OWAPI_CTRL_User', 'extra', array('userId'=>array(OW_Route::PARAM_OPTION_DEFAULT_VALUE => null))));
OW::getRouter()->addRoute(new OW_Route('owapi.user.updateProfile', '/owapi/user/update/', 'OWAPI_CTRL_User', 'updateProfile'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.updateAvatar', '/owapi/user/update/avatar', 'OWAPI_CTRL_User', 'updateAvatar'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.all', '/owapi/user/all/:first', 'OWAPI_CTRL_User', 'all'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.isFavorite', '/owapi/user/isFavorite/:favoriteId', 'OWAPI_CTRL_User', 'isFavorite'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.favorite', '/owapi/user/favorite/:command/:favoriteId', 'OWAPI_CTRL_User', 'favorite'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.favorite.list', '/owapi/user/favorite/list', 'OWAPI_CTRL_User', 'favoriteList'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.isFriend', '/owapi/user/isfriend/:friendId', 'OWAPI_CTRL_User', 'isFriend'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.friend', '/owapi/user/friend/:command/:friendId', 'OWAPI_CTRL_User', 'friend'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.friendList', '/owapi/messenger/friendList', 'OWAPI_CTRL_User', 'friendList'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.friend.requestList', '/owapi/user/friend/requestList', 'OWAPI_CTRL_User', 'friendRequestList'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.isBlocked', '/owapi/user/isblocked/:userId', 'OWAPI_CTRL_User', 'isBlocked'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.block', '/owapi/user/block/:command/:userId', 'OWAPI_CTRL_User', 'block'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.flag', '/owapi/user/flag/:reason/:userId', 'OWAPI_CTRL_User', 'flag'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.near', '/owapi/user/near/:distance', 'OWAPI_CTRL_User', 'near'));
OW::getRouter()->addRoute(new OW_Route('owapi.user.update.location', '/owapi/user/updateLocation', 'OWAPI_CTRL_User', 'updateLocation'));

OW::getRouter()->addRoute(new OW_Route('owapi.user.delete', '/owapi/user/delete', 'OWAPI_CTRL_User', 'delete'));

/*** Messenger ***/
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversationList', '/owapi/messenger/conversationList', 'OWAPI_CTRL_Messenger', 'conversationList'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.getConversationWithOpponentId', '/owapi/messenger/conversation/opponent/:opponentId', 'OWAPI_CTRL_Messenger', 'getConversationWithOpponentId'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.contactList', '/owapi/messenger/contactList', 'OWAPI_CTRL_Messenger', 'contactList'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation', '/owapi/messenger/conversation/:conversationId', 'OWAPI_CTRL_Messenger', 'conversation'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.new', '/owapi/messenger/conversation/:conversationId/unread/:lastMessage', 'OWAPI_CTRL_Messenger', 'conversationUnread'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.history', '/owapi/messenger/conversation/:conversationId/history/:lastMessage', 'OWAPI_CTRL_Messenger', 'conversationHistory'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.send', '/owapi/messenger/conversation/:conversationId/send', 'OWAPI_CTRL_Messenger', 'conversationSend'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.create', '/owapi/messenger/conversation/create', 'OWAPI_CTRL_Messenger', 'conversationCreate'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.get', '/owapi/messenger/conversation/get/:opponentId', 'OWAPI_CTRL_Messenger', 'getConversationWithOpponentId'));
OW::getRouter()->addRoute(new OW_Route('owapi.messenger.conversation.read', '/owapi/messenger/conversation/markRead/:conversationId', 'OWAPI_CTRL_Messenger', 'conversationMarkRead'));

/*** Device ***/
OW::getRouter()->addRoute(new OW_Route('owapi.device.register', '/owapi/device/register', 'OWAPI_CTRL_Device', 'register'));
OW::getRouter()->addRoute(new OW_Route('owapi.device.deregister', '/owapi/device/deregister', 'OWAPI_CTRL_Device', 'deregister'));

/*** Admin ***/
OW::getRouter()->addRoute(new OW_Route('owapi.admin', 'admin/owapi', 'OWAPI_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('owapi.admin.push.ios', 'admin/owapi/push/ios', 'OWAPI_CTRL_Admin', 'pushIos'));
OW::getRouter()->addRoute(new OW_Route('owapi.admin.push.android', 'admin/owapi/push/android', 'OWAPI_CTRL_Admin', 'pushAndroid'));

/*** Deploy ***/
OW::getRouter()->addRoute(new OW_Route('owapi.deploy', '/owapi/deploy', 'OWAPI_CTRL_Deploy', 'index'));
OW::getRouter()->addRoute(new OW_Route('owapi.deploy.manifest', '/owapi/delopy/manifest', 'OWAPI_CTRL_Deploy', 'manifest'));

/*** Google & Facebook Connect ***/
OW::getRouter()->addRoute(new OW_Route('owapi.site.glconnect', '/owapi/site/glconnect', 'OWAPI_CTRL_Site', 'glConnect'));
OW::getRouter()->addRoute(new OW_Route('owapi.site.fbconnect', '/owapi/site/fbconnect', 'OWAPI_CTRL_Site', 'fbConnect'));


$eventHandler =  new OWAPI_CLASS_EventHandler();
$eventHandler->init();