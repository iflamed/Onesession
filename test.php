<?php
require 'php/MemcacheSession.php';
$cacheConfig = array(
	array(
			'host'=>'10.16.1.240',
			'port'=>10002,
			'weight'=>6,
	),
	array(
			'host'=>'10.16.1.240',
			'port'=>10003,
			'weight'=>4,
	)
);
MemcacheSession::init($cacheConfig);
session_start();
$_SESSION['serialisation'] = 'should be in json';
$_SESSION['a']++;
var_dump($_SESSION);
?>