<?php

    /**
     * @package facebook
     * @author Peter Nemeth, sokkis@gmail.com
     * @version v1.0
     */


/**
 * Facebook service api
 * @author Peter Nemeth
 */
class FacebookApi 
{
    /**
     * Instance of the $api 
     * @static FacebookApi $api 
     */
    public static $api = null;
    
    protected $session   = null;
    protected $facebook  = null;
    public    $user      = null;
    
    public $permissions = null;
    private $_perms = null;
        
    private $cache = array();
       
    /**
     * constructor
     * @param array $facebookSettings
     * array(
     *      'appId'  => 'Your application id',
     *      'secret' => 'Your app secret',
     *      'cookie' => true/false
     * );
     */     
    private function __construct($facebookSettings,$permissions)
    {
        self::$api = &$this;
        if(!$facebookSettings)
        {
            $facebookSettings = array
            (
                "appId"=>FacebookSettings::$appId,
                "secret"=>FacebookSettings::$secret,
                "cookie"=>FacebookSettings::$cookie
            );
        }
        $this->facebook = new Facebook($facebookSettings);
        $this->session = $this->facebook->getSession();
        $uid = null;
        if($this->session)
        {
            $uid = $this->facebook->getUser(); 
        }
        if(!$uid)
        {
            $this->changePerms($permissions);
        }
        //$this->permissions = $permissions;        
        $this->user = new FacebookUser();
        $this->permissions = $this->user->permissions;
        $this->_perms = $this->user->permissions;
    }
    
   /**
    * Get the current instance of the api
    * if don't have, create it
    * @param array $facebookSettings
    * array(
    *      'appId'  => 'Your application id',
    *      'secret' => 'Your app secret',
    *      'cookie' => true/false
    * );
    * @param FacebookPermissions $permissions
    */
    public static function &getInstance($permissions = null,$facebookSettings = null)
    {
     if(!self::$api) new self($facebookSettings,$permissions);
     return self::$api;
    }    

    /**
     * Invalidates the request cache
     */ 
    public function invalidate()
    {
        $this->cache = null;
    }

    private function needauth($newpermissions)
    {
        
        if(!$this->permissions) return true;
        foreach($newpermissions as $k => $v )
        {
            if((!isset($this->permissions->$k) || !$this->permissions->$k) && $newpermissions->$k)
            {                
                return true;
            }
        }
        return false;
    }        
        
    public function changePerms($permissions, $force = false, $next='')
    {        
        if($this->needauth($permissions) || $force )
        {            
            header("Location: ".$this->getLoginUrl($permissions,$next));
            die();
        }
    }

    /**
     * Gets the login url
     * @param FacebookPermissions $permissions
     * @param string next return url after auth, if empty, $_SERVER['REQUEST_URI'] used
     * @return string
     */
    public function getLoginUrl($permissions, $next='')
    {
     $next = $next?$next:"http://{$_SERVER['REQUEST_URI']}";
     $params = array(
        'req_perms'=>strval($permissions)
        //'next'=>$next
//        'fbconnect'=>0,
//        'canvas' => 0
     );
     $this->permissions = $permissions;
     return $this->facebook->getLoginUrl($params);
    }

    /**
     * Fetches the raw data object from facebook feed
     * @param string request
     */          
    public function api($request, $method='',$att='')
    {        
        if($request[0]!='/') $request='/'.$request;
        if(!isset($this->cache[$request]))
            if($method)
            {
                
                $this->cache[$request] = $this->facebook->api($request,$method,$att);
            }
            else
            {
                $this->cache[$request] = $this->facebook->api($request);
            }
         return $this->cache[$request];
    }

    function __destruct()
    {
        
    }
    
}

?>