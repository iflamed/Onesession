<?php
/**
 * only surport the php>=5.4
 */
if (interface_exists('SessionHandlerInterface')){
    class MemcacheSessionHandler54  implements SessionHandlerInterface{
        private $lifetime = 0;
        private $memcache = null;
        public static $config = array();
        public static $useMemcached=false;
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
        public static function init($config,$useMemcached=false){
            self::$config = $config;
            self::$useMemcached = $useMemcached;
            $handler = new MemcacheSessionHandler54();
            return $handler;
        }

        public function getMemCache(){
            if($this->memcache!==null)
                return $this->memcache;
            else
            {
                $extension=self::$useMemcached ? 'memcached' : 'memcache';
                if(!extension_loaded($extension))
                    throw new Exception("MemcacheSession requires PHP extension to be loaded");
                return $this->memcache=self::$useMemcached ? new Memcached : new Memcache;
            }
        }

        /**
         * Constructor
         */
        public function __construct(){
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
            $_SESSION = json_decode($this->memcache->get("sessions/{$id}"), true);
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
            return self::$useMemcached?$this->memcache->set("sessions/{$id}", json_encode($new_data), $this->lifetime):$this->memcache->set("sessions/{$id}", json_encode($new_data), 0, $this->lifetime);
        }
     
        /**
         * Delete object in session
         * @param string $id The SESSID to delete
         * @return boolean True if memcached was able delete session data
         */
        public function destroy($id){
            return $this->memcache->delete("sessions/{$id}");
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
}

/**
* 
*/
class MemcacheSessionHandler53
{
    
    private $lifetime = 0;
    private $memcache = null;
    public static $config = array();
    public static $useMemcached=false;
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
     * @return MemcacheSessionHandler53
     */
    public static function init($config,$useMemcached=false){
        self::$config = $config;
        self::$useMemcached = $useMemcached;
        $handler = new MemcacheSessionHandler53();
        return $handler;
    }

    public function getMemCache(){
        if($this->memcache!==null)
            return $this->memcache;
        else
        {
            $extension=self::$useMemcached ? 'memcached' : 'memcache';
            if(!extension_loaded($extension))
                throw new Exception("MemcacheSession requires PHP extension to be loaded");
            return $this->memcache=self::$useMemcached ? new Memcached : new Memcache;
        }
    }

    /**
     * Constructor
     */
    public function __construct(){
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
                $this->memcache->addServer($host,$port,$weight);
            else
                $this->memcache->addServer($host, $port,$persistent,$weight,$timeout,$retry_interval,$status);
        }
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
        $_SESSION = json_decode($this->memcache->get("sessions/{$id}"), true);
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
        return self::$useMemcached?$this->memcache->set("sessions/{$id}", json_encode($new_data), $this->lifetime):$this->memcache->set("sessions/{$id}", json_encode($new_data), 0, $this->lifetime);
    }
 
    /**
     * Delete object in session
     * @param string $id The SESSID to delete
     * @return boolean True if memcached was able delete session data
     */
    public function destroy($id){
        return $this->memcache->delete("sessions/{$id}");
    }
 
    /**
     * Close gc
     * @return boolean Always true
     */
    public function gc(){
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
* 
*/
class MemcacheSession
{
    
    public static function init($config,$useMemcached=false)
    {
        if (interface_exists('SessionHandlerInterface')){
            return MemcacheSessionHandler54::init($config,$useMemcached);
        }else{
            return MemcacheSessionHandler53::init($config,$useMemcached);
        }
    }
}
?>