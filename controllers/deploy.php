<?php

/**
 * Description of Deploy Ctrl Class
 *
 * @author Kalianey
 */

class OWAPI_CTRL_Deploy extends OWAPI_CLASS_ApiController
{
    function index()
    {
        header('Content-type: application/xml');
        
    }
    
    function manifest()
    {
        header('Content-type: application/octet-stream');
        
    }
    
}