<?php

if(!interface_exists('SessionHandlerInterface')){
    /**
     * SessionHandlerInterface
     *
     * Provides forward compatibility with PHP 5.4
     *
     * Extensive documentation can be found at php.net, see links:
     *
     * @see http://php.net/sessionhandlerinterface
     * @see http://php.net/session.customhandler
     * @see http://php.net/session-set-save-handler
     *
     * @author Drak <drak@zikula.org>
     */
    interface SessionHandlerInterface
    {
        /**
         * Open session.
         *
         * @see http://php.net/sessionhandlerinterface.open
         *
         * @param string $savePath    Save path.
         * @param string $sessionName Session Name.
         *
         * @throws \RuntimeException If something goes wrong starting the session.
         *
         * @return boolean
         */
        public function open($savePath, $sessionName);

        /**
         * Close session.
         *
         * @see http://php.net/sessionhandlerinterface.close
         *
         * @return boolean
         */
        public function close();

        /**
         * Read session.
         *
         * @param string $sessionId
         *
         * @see http://php.net/sessionhandlerinterface.read
         *
         * @throws \RuntimeException On fatal error but not "record not found".
         *
         * @return string String as stored in persistent storage or empty string in all other cases.
         */
        public function read($sessionId);

        /**
         * Commit session to storage.
         *
         * @see http://php.net/sessionhandlerinterface.write
         *
         * @param string $sessionId Session ID.
         * @param string $data      Session serialized data to save.
         *
         * @return boolean
         */
        public function write($sessionId, $data);

        /**
         * Destroys this session.
         *
         * @see http://php.net/sessionhandlerinterface.destroy
         *
         * @param string $sessionId Session ID.
         *
         * @throws \RuntimeException On fatal error.
         *
         * @return boolean
         */
        public function destroy($sessionId);

        /**
         * Garbage collection for storage.
         *
         * @see http://php.net/sessionhandlerinterface.gc
         *
         * @param integer $lifetime Max lifetime in seconds to keep sessions stored.
         *
         * @throws \RuntimeException On fatal error.
         *
         * @return boolean
         */
        public function gc($lifetime);
    }
}

class MemcacheSessionHandler implements SessionHandlerInterface
{
    
    private $lifetime = 0;
    private $memcache = null;
    public static $config = array();
    public static $useMemcached=false;
    public static $keyPrefix = 'sessions/';

    /**
     * init a memcache session handler, php>=5.4
     * @param  array $config 
     * array(
     *     'host'=>,
     *     'port'=>,
     *     'persistent'=>,
     *     'weight'=>,
     *     'timeout'=>,
     *     'retry_interval'=>,
     *     'status'=>
     * )
     * @return MemcacheSessionHandler54
     */
    public static function init($config,$keyPrefix='',$useMemcached=false){
        self::$config = $config;
        self::$useMemcached = $useMemcached;
        if ($keyPrefix) {
            self::$keyPrefix=$keyPrefix;
        }
        return static::getHandler();
    }

    public static function getHandler(){
        return new MemcacheSessionHandler();
    }

    public function getMemCache(){
        if($this->memcache!==null)
            return $this->memcache;
        else
        {
            $extension=self::$useMemcached ? 'memcached' : 'memcache';
            if(!extension_loaded($extension))
                throw new Exception("MemcacheSession requires PHP {$extension} extension to be loaded");
            return $this->memcache=self::$useMemcached ? new Memcached : new Memcache;
        }
    }

    /**
     * Constructor
     */
    public function __construct(){
        $this->setServers();
        $this->setSessionHandler();
    }

    public function setServers(){
        $this->getMemCache();
        foreach (self::$config as $key => $server) {
            $host = $server['host'];
            $port = isset($server['port'])?$server['port']:11211;
            $persistent = isset($server['persistent'])?$server['persistent']:true;
            $weight = isset($server['weight'])?$server['weight']:50;
            $timeout = isset($server['timeout'])?$server['timeout']:1;
            $retry_interval = isset($server['retry_interval'])?$server['retry_interval']:15;
            $status = isset($server['status'])?$server['status']:true;
            if(self::$useMemcached)
                $$this->memcache->addServer($host,$port,$weight);
            else
                $this->memcache->addServer($host, $port,$persistent,$weight,$timeout,$retry_interval,$status);
        }
    }

    public function setSessionHandler(){
        session_set_save_handler($this, true);
    }
 
    /**
     * Destructor
     */
    public function __destruct(){
        session_write_close();
        (self::$useMemcached==false)?$this->memcache->close():'';
    }
 
    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime
     * @return boolean True if everything succeed
     */
    public function open($savePath, $sessionName){
        $this->lifetime = ini_get('session.gc_maxlifetime');
        return true;
    }
 
    /**
     * Read the id
     * @param string $id The SESSID to search for
     * @return string The session saved previously
     */
    public function read($id){
        $tmp = $_SESSION;
        $_SESSION = json_decode($this->memcache->get(self::$keyPrefix."{$id}"), true);
        if(isset($_SESSION) && !empty($_SESSION) && $_SESSION != null){
            $new_data = session_encode();
            $_SESSION = $tmp;
            return $new_data;
        }else{
            return "";
        }
    }
 
    /**
     * Write the session data, convert to json before storing
     * @param string $id The SESSID to save
     * @param string $data The data to store, already serialized by PHP
     * @return boolean True if memcached was able to write the session data
     */
    public function write($id, $data){
        $tmp = $_SESSION;
        session_decode($data);
        $new_data = $_SESSION;
        $_SESSION = $tmp;
        return self::$useMemcached?$this->memcache->set(self::$keyPrefix."{$id}", json_encode($new_data), $this->lifetime):$this->memcache->set(self::$keyPrefix."{$id}", json_encode($new_data), 0, $this->lifetime);
    }
 
    /**
     * Delete object in session
     * @param string $id The SESSID to delete
     * @return boolean True if memcached was able delete session data
     */
    public function destroy($id){
        return $this->memcache->delete(self::$keyPrefix."{$id}");
    }
 
    /**
     * Close gc
     * @return boolean Always true
     */
    public function gc($lifetime){
        return true;
    }
 
    /**
     * Close session
     * @return boolean Always true
     */
    public function close(){
        return true;
    }
}

/**
 * compatible for the php version less than 5.4
 */
class MemcacheSessionHandlerCompatible extends MemcacheSessionHandler
{
    /**
     * Instance a memcache session handler
     * @return Object Instance of MemcacheSessionHandlerCompatible
     */
    public static function getHandler(){
        return new MemcacheSessionHandlerCompatible();
    }

    /**
     * register the session handler
     */
    public function setSessionHandler(){
        session_set_save_handler(
            array($this, 'open'),    
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
        register_shutdown_function('session_write_close');
    }
 
}
/**
* 
*/
class MemcacheSession
{
    
    public static function init($config,$keyPrefix='',$useMemcached=false)
    {
        $phpVersion = phpVersion();
        if (version_compare($phpVersion, '5.4')!=-1){
            return MemcacheSessionHandler::init($config,$keyPrefix,$useMemcached);
        }else{
            return MemcacheSessionHandlerCompatible::init($config,$keyPrefix,$useMemcached);
        }
    }
}
?>