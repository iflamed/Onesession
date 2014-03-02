<?php
require 'php/MemcacheSession.php';
$cacheConfig = array(
	'config'=>array(
		array(
				'host'=>'127.0.0.1',
				'port'=>11211,
				'weight'=>6,
		),
	),
	'useMemcached'=>false,
);
$keyPrefix='ruobiyi.com';
$storeClassName = 'MemcacheStore';
MemcacheSession::init($storeClassName,$cacheConfig,$keyPrefix);
session_start();
$_SESSION['serialisation'] = 'should be in json';
if (!isset($_SESSION['a'])) {
	$_SESSION['a'] = 0;
}
$_SESSION['a']++;
var_dump($_SESSION);
?>