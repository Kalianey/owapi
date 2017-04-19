<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class OWAPI_CTRL_Deploy extends OWAPI_CLASS_ApiController
{
    function index()
    {
        header('Content-type: application/xml');
        //$manifestUrl = OW::getRoute->
        
    }
    
    function manifest()
    {
        header('Content-type: application/octet-stream');
        
       	/*			
        <string>{$ipaUrl}</string>
				</dict>
			</array>
			<key>metadata</key>
			<dict>
				<key>bundle-identifier</key>
				<string>{$bundleId}</string>
				<key>bundle-version</key>
				<string>{$version}</string>
				<key>kind</key>
				<string>software</string>
				<key>title</key>
				<string>{$title}</string> 
                */
    }
    
}