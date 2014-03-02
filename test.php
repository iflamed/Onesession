<?php
require 'php/MemcacheSession.php';
$cacheConfig = array(
	array(
			'host'=>'127.0.0.1',
			'port'=>11211,
			'weight'=>6,
	)
);
$keyPrefix='ruobiyi.com';
MemcacheSession::init($cacheConfig,$keyPrefix);
session_start();
$_SESSION['serialisation'] = 'should be in json';
if (!isset($_SESSION['a'])) {
	$_SESSION['a'] = 0;
}
$_SESSION['a']++;
var_dump($_SESSION);
?>