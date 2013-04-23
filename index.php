<?php
define('SharpPHP', 'v0.1');

// 配置信息
$config = array(
		// 项目
		'app_path'   => __DIR__.'/examples',
		'app_module' => 'Home',
		
		// 数据库
		'db_host' => '127.0.0.1',
		'db_user' => 'root',
		'db_pass' => '123456',
		'db_name' => 'blog',
		
		// 模型
		'model_prefix' => 'sharp_',
);

include 'SharpPHP/SharpPHP.class.php';

$sharpphp = new SharpPHP($config);
list($config, $pdo, $view, $controller) = $sharpphp->init();
$sharpphp->run();