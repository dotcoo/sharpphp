<?php
define('SharpPHP', 'v0.1');

// 配置信息
$config = array(
		'db' => array(
				'host' => '127.0.0.1', 		// 数据库主机
				'user' => 'root',			// 数据库账户
				'pass' => '123456',			// 数据库密码
				'name' => 'blog',			// 数据库名
		),
);

include 'SharpPHP/SharpPHP.class.php';

$sharpphp = new SharpPHP();
list($config, $pdo, $view, $controller) = $sharpphp->init($config);
$sharpphp->run();