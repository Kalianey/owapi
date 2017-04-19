<?php

/**
 * Description of device_dao
 *
 * @author Kalianey
 */

class OWAPI_BOL_DeviceDao extends OW_BaseDao
{
    const DEVICE_KIND_IOS = 'ios';
    const DEVICE_KIND_ANDROID = 'android';
    
    
    /**
     * Singleton instance.
     *
     * @var OWAPI_BOL_DeviceDao
     */
    private static $classInstance;
    
    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OWAPI_BOL_DeviceDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getDtoClassName()
    {
        return 'OWAPI_BOL_Device';
    }
    
    public function getTableName()
    {
        return OW_DB_PREFIX . "owapi_device";
    }
    
    public function findByToken($token)
    {
        $example = new OW_Example();
        $example->andFieldEqual('token', $token);
        
        return $this->findObjectByExample($example);
    }
    
    public function findByUserId($userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        
        return $this->findListByExample($example);
    }
}
