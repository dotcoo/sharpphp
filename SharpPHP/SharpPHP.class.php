<?php
if (!defined('SharpPHP')) exit();

include 'common.func.php';

/**
 * SharpPHP class
 * @author dotcoo zhao <dotcoo@gmail.com>
 * @link https://github.org/dotcoo/sharpphp
 */
class SharpPHP {
	private $config = array();
	private $controller;
	private $view;
	private $pdo;

	// 构造函数
	function __construct() {}
	
	// 获取配置信息
	public function getConfig($name=null) {
		if (isset($name)) {
			return isset($this->config[$name]) ? $this->config[$name] : array();
		} else {
			return $this->config;
		}
	}
	
	// 设置配置信息
	public function setConfig($name, $value=null) {
		if (isset($value)) {
			$this->config[$name] = $value;
		} else {
			$this->config = $name;
		}
	}
	
	// 获取控制器
	public function getController() {
		return $this->controller;
	}
	
	// 设置控制器
	public function setController($controller) {
		$this->controller = $controller;
	}
	
	// 获取试图
	public function getView() {
		return $this->view;
	}
	
	// 设置试图
	public function setView($view) {
		$this->view = $view;
	}
	
	// 获取试图
	public function getPDO() {
		return $this->pdo;
	}
	
	// 初始化SharpPHP
	public function init($config) {
		$this->setConfig($config);
		
		// 应用默认配置
		$this->initConfig();
		
		// 初始化常量
		$this->initConst();
		
		// 自动加载类
		spl_autoload_register(array($this, 'autoload'));
		
		// 连接数据库
		$pdo = $this->createPDO();
		
		// 创建视图
		$view = $this->createView();
		
		// 创建控制器
		$controller = $this->createColltroller();
		
		return array($this->getConfig(), $pdo, $view, $controller);
	}
	
	// 配置文件
	private function initConfig() {
		// 基本配置
		$config_app = $this->getConfig('app');
		$default_app = array(
				'path' => $_SERVER['DOCUMENT_ROOT'].'/App', // 项目路径
				'module' => 'Web', 						// 默认模块
		);
		$config_app = array_merge($default_app, $config_app);
		$this->setConfig('app', $config_app);
		
		// 数据库配置
		$config_db = $this->getConfig('db');
		$default_db = array(
				'host' => '127.0.0.1', 	// 数据库地址
				'user' => 'root', 		// 数据库用户
				'pass' => '', 			// 数据库密码
				'name' => 'test', 		// 数据库名称
				'charset' => 'utf8', 	// 数据库编码
		);
		$config_db = array_merge($default_db, $config_db);
		$config_db['options'] = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => "set names {$config_db['charset']};",	// 连接字符集
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,		// 显示SQL错误
		);
		$this->setConfig('db', $config_db);
	}
	
	// 初始化常量
	private function initConst() {
		// SharpPHP根目录
		define('SHARPPHP_PATH', __DIR__);
		
		$config_app = $this->getConfig('app');
		// 项目根目录
		define('APP_PATH', $config_app['path']);
		// 默认模块
		define('APP_MODULE', $config_app['module']);
		
		// 获得请求的Controller和Action
		define('APP_CONTROLLER', ucfirst(isset($_GET['c']) ? $_GET['c'] : 'Index'));
		define('APP_ACTION', isset($_GET['a']) ? $_GET['a'] : 'index');
	}
	
	// 自动载入
	public function autoload($class_name) {
		$core_class = array('Controller', 'Model', 'Page', 'View');
		
		if (in_array($class_name, $core_class)) {
			// 核心类
			include SHARPPHP_PATH.'/'.$class_name.'.class.php';
		} elseif (substr($class_name, -5) == 'Model') {
			// 模型
			include APP_PATH.'/Model/'.$class_name.'.class.php';
			return;
		} elseif (substr($class_name, -10) == 'Controller') {
			// 控制器
			include APP_PATH.'/Controller/'.APP_MODULE.'/'.$class_name.'.class.php';
			return;
		} else {
			throw new Exception("SharpPHP: class $class_name not found!");
		}
	}
	
	// 创建PDO对象
	private function createPDO() {
		$config_db = $this->getConfig('db');
		$dsn = "mysql:host={$config_db['host']};dbname={$config_db['name']}";
		
		$pdo = new PDO($dsn, $config_db['user'], $config_db['pass'], $config_db['options']);
		
		$this->pdo = $pdo;
		Model::$sharpphp = $this;
		return $pdo;
	}
	
	// 创建View对象
	private function createView() {
		$tplpath = APP_PATH.'/View/'.APP_MODULE;
		$outpath = APP_PATH.'/Data/View/'.APP_MODULE;
		$name = APP_CONTROLLER.'/'.APP_ACTION;
		
		$view = new View($tplpath, $outpath, $name);
		$view->sharpphp = $this;
		$view->initConfig();
		
		$this->view = $view;
		return $view;
	}
	
	private function createColltroller() {
		$controller_name = APP_MODULE.APP_CONTROLLER.'Controller';
		
		if (!class_exists($controller_name)) {
			throw new Exception("SharpPHP: class {$controller_name} not found!");
		}
		
		$controller = new $controller_name();
		$controller->sharpphp = $this;
		
		$this->controller = $controller;
		return $controller;
	}
	
	// 运行
	public function run() {
		$action_name = APP_ACTION.'Action';
		$action_name = method_exists($this->controller, $action_name) ? $action_name : 'notFoundAction';
		
		$this->controller->{$action_name}($this->view);
	}
}