<?php
require 'vendor/autoload.php';
$cacheConfig = array(
	'config'=>array(
		array(
				'host'=>'10.16.1.240',
				'port'=>10003,
				'weight'=>6,
		),
	),
	'useMemcached'=>false,
);
$keyPrefix='ruobiyi.com';
$storeClassName = 'MemcacheStore';
Onesession\HttpSession::init($storeClassName,$cacheConfig,$keyPrefix);
session_start();
$_SESSION['serialisation'] = 'should be in json';
if (!isset($_SESSION['a'])) {
	$_SESSION['a'] = 0;
}
$_SESSION['a']++;
var_dump($_SESSION);
?>