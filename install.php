<?php
/**
 * Copyright (c ) 2014 by iHands, Inc. All Rights Reserved.
 * This is property of iHands, any modifications to this file must keep this entire header intact.
 * iHands website : http://www.ihands.com.vn
 * License        : http://www.ihands.com.vn/license
 * Project        : iMod
 * FileName       : install.php
 */

OW::getPluginManager()->addPluginSettingsRouteName('owapi', 'owapi.admin');

$path = OW::getPluginManager()->getPlugin('owapi')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'owapi');


$config = OW::getConfig()->getInstance();

$config->addConfig('owapi', 'ios_push_is_enable',true);
$config->addConfig('owapi', 'ios_push_is_sandbox',true);
$config->addConfig('owapi', 'ios_push_cert_path','');

$configDst = OW::getPluginManager()->getPlugin('owapi')->getPluginFilesDir().'config/';
@mkdir($configDst);


$sql = 'CREATE TABLE `'.OW_DB_PREFIX.'owapi_device` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `userId` int(11) NOT NULL,
  `last_update` varchar(50) NOT NULL,
  `kind` varchar(100) NOT NULL,
  `push_enable` tinyint(1) NOT NULL,
  `token` varchar(255) NOT NULL,
  `extra` text NOT NULL,
   primary key(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
OW::getDbo()->query($sql);


$functionSql = "DELIMITER $$
                DROP FUNCTION IF EXISTS haversine$$

                CREATE FUNCTION haversine(
                        lat1 FLOAT, lon1 FLOAT,
                        lat2 FLOAT, lon2 FLOAT
                     ) RETURNS FLOAT
                    NO SQL DETERMINISTIC
                    COMMENT 'Returns the distance in degrees on the Earth
                             between two known points of latitude and longitude'
                BEGIN
                    RETURN DEGREES(ACOS(
                              COS(RADIANS(lat1)) *
                              COS(RADIANS(lat2)) *
                              COS(RADIANS(lon2) - RADIANS(lon1)) +
                              SIN(RADIANS(lat1)) * SIN(RADIANS(lat2))
                            ));
                END$$

                DELIMITER ;";
OW::getDbo()->query($sql);
