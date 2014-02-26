<?php
/**
 * only surport the php>=5.4
 */
class MemcacheSessionHandler  implements SessionHandlerInterface{
    private $lifetime = 0;
    private $memcache = null;
    public static $config = array();

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
     * @return MemcacheSessionHandler
     */
    public static function init($config){
        self::$config = $config;
        $handler = new MemcacheSessionHandler();
        return $handler;
    }

    /**
     * Constructor
     */
    public function __construct(){
        $this->memcache = new Memcache;
        foreach (self::$config as $key => $server) {
            $host = $server['host'];
            $port = isset($server['port'])?$server['port']:0;
            $persistent = isset($server['persistent'])?$server['persistent']:true;
            $weight = isset($server['weight'])?$server['weight']:50;
            $timeout = isset($server['timeout'])?$server['timeout']:1;
            $retry_interval = isset($server['retry_interval'])?$server['retry_interval']:15;
            $status = isset($server['status'])?$server['status']:true;
            $this->memcache->addServer($host, $port,$persistent,$weight,$timeout,$retry_interval,$status);
        }
        session_set_save_handler(&$this, true);
    }
 
    /**
     * Destructor
     */
    public function __destruct(){
        session_write_close();
        $this->memcache->close();
    }
 
    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime
     * @return boolean True if everything succeed
     */
    public function open(){
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
        return $this->memcache->set("sessions/{$id}", json_encode($new_data), 0, $this->lifetime);
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
?>