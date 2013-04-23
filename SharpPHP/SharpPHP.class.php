<?php
if (!defined('SharpPHP')) exit();

include 'common.func.php';

/**
 * SharpPHP class
 * @author dotcoo zhao <dotcoo@gmail.com>
 * @link https://github.org/dotcoo/sharpphp
 */
class SharpPHP {
	public $controller;
	public $view;
	
	public $controller_name = 'index';
	public $action_name = 'index';

	// 构造函数
	function __construct(&$config) {
		$this->_config = $config;
	}
	
	// 初始化SharpPHP
	public function init() {
		// SharpPHP路径
		define('SHARPPHP_PATH', __DIR__);
		
		$config = $this->config($this->_config);
		
		// 自动加载类
		spl_autoload_register(array($this, 'autoload'));
		
		// 获得请求的Controller和Action
		define('APP_CONTROLLER', ucfirst(isset($_GET['c']) ? $_GET['c'] : 'Index'));
		define('APP_ACTION', isset($_GET['a']) ? $_GET['a'] : 'Index');
		$this->controller_name = APP_MODULE.APP_CONTROLLER.'Controller';
		$this->action_name = APP_ACTION.'Action';
		
		// 连接数据库
		$pdo = $this->createPdo($config);
		
		// 创建视图
		$view = $this->createView($config);
		
		// 创建控制器
		$controller = $this->createColltroller($config);
		
		// 模型
		Model::$global_pdo = $pdo;
		Model::$global_prefix = $config['model_prefix'];
		Model::$global_pk = $config['model_pk'];
		Model::$global_pagesize = $config['model_pagesize'];
		
		return array($config, $pdo, $view, $controller);
	}
	
	// 配置文件
	public function config(&$config) {
		// 配置信息
		$default = array(
				// 项目
				'app_path' => $_SERVER['DOCUMENT_ROOT'].'/App', // 项目路率
				'app_module' => 'Home', // 默认模块
		
				// 数据库
				'db_host' => '127.0.0.1', // 数据库地址
				'db_user' => 'root', // 数据库用户
				'db_pass' => '', // 数据库密码
				'db_name' => 'text', // 数据库名称
				'db_charset' => 'utf8', // 数据库编码
				'db_options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8;'),
		
				// 模型
				'model_prefix' => '', // 表前缀
				'model_pk' => 'id', // 默认主键名称
				'model_pagesize' => 15, // 默认分页打消
		
				// 试图
				'view_extension' => '.htm', // 模板扩展名
				'view_charset' => 'utf-8', // 默认字符编码
				'view_check' => true, // 检查模板更新
				'view_message_name' => '', // 消息模板名称
		);
		$config = array_merge($default, $config);
		
		define('APP_PATH', $config['app_path']);
		define('APP_MODULE', $config['app_module']);
		
		return $config;
	}
	
	// 运行
	public function run() {
		if (!method_exists($this->controller, $this->action_name)) {
			$this->controller->{'notFoundAction'}($this->view);
			return;
		}
		$this->controller->{$this->action_name}($this->view);
	}
	
	// 自动载入
	public function autoload($class_name) {
		// 核心类
		$core_class = array('Cache', 'Controller', 'Image', 'Log', 'Model', 'Page', 'View');
		if (in_array($class_name, $core_class)) {
			include SHARPPHP_PATH.'/'.$class_name.'.class.php';
			return;
		}
		// 模型
		if (substr($class_name, -5) == 'Model') {
			include APP_PATH.'/Model/'.$class_name.'.class.php';
			return;
		}
		// 控制器
		if (substr($class_name, -10) == 'Controller') {
			include APP_PATH.'/Controller/'.APP_MODULE.'/'.$class_name.'.class.php';
			return;
		}
	}
	
	public function createPdo(&$config) {
		if (!is_array($config['db_options'])) {
			$config['db_options'] = array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names {$config['charset']};");
		}
	
		$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']}";
		$this->pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $config['db_options']);
		return $this->pdo;
	}
	
	public function createView(&$config) {
		$this->view = new View(APP_PATH.'/View/'.APP_MODULE, APP_PATH.'/Data/View/'.APP_MODULE, APP_CONTROLLER.'/'.APP_ACTION);
		$this->view->extension = $config['view_extension'];
		$this->view->check = $config['view_check'];
		$this->view->charset = $config['view_charset'];
		$this->view->message_name = $config['view_message_name'];
		$this->view->config = $config;
		return $this->view;
	}
	
	public function createColltroller(&$config) {
		if (!class_exists($this->controller_name)) {
			if (!class_exists(APP_MODULE.'EmptyController')) {
				throw new Exception("SharpPHP: class {$this->controller_name} not found!");
			}
			$this->controller_name = 'EmptyController';
			$this->action_name = 'indexAction';
		}
// 		$class_name = $this->controller_name;
		$this->controller = new $this->controller_name();
		$this->controller->config = $config;
		return $this->controller;
	}
}