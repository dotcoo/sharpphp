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
	
	public $controller_name;
	public $action_name;

	/**
	 * 构造函数
	 * @param string $controller_name 请求的控制器
	 * @param string $action_name 请求的方法
	 */
	function __construct($controller_name = null, $action_name = null) {
		$this->controller_name = $controller_name;
		$this->action_name = $action_name;
	}
	
	/**
	 * 获取配置信息
	 * @param string $name 配置名称
	 * @return array
	 */
	public function getConfig($name=null) {
		if (isset($name)) {
			return isset($this->config[$name]) ? $this->config[$name] : array();
		} else {
			return $this->config;
		}
	}
	
	/**
	 * 设置配置信息
	 * @param string $name
	 * @param string $value
	 */
	public function setConfig($name, $value=null) {
		if (isset($value)) {
			$this->config[$name] = $value;
		} else {
			$this->config = $name;
		}
	}
	
	/**
	 * 获取控制器
	 */
	public function getController() {
		return $this->controller;
	}
	
	/**
	 * 设置控制器
	 * @param Controller $controller
	 */
	public function setController($controller) {
		$this->controller = $controller;
	}
	
	/**
	 * 获取试图
	 */
	public function getView() {
		return $this->view;
	}
	
	/**
	 * 设置试图
	 * @param View $view
	 */
	public function setView($view) {
		$this->view = $view;
	}
	
	/**
	 * 获取试图
	 */
	public function getPDO() {
		return $this->pdo;
	}
	
	/**
	 * 初始化SharpPHP
	 * @param array $config
	 * @return array
	 */
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
	
	/**
	 * 初始化配置
	 */
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
	
	/**
	 * 初始化常量
	 */
	private function initConst() {
		// SharpPHP根目录
		define('SHARPPHP_PATH', __DIR__);
		
		$config_app = $this->getConfig('app');
		// 项目根目录
		define('APP_PATH', $config_app['path']);
		// 默认模块
		define('APP_MODULE', $config_app['module']);
		
		// 获得请求的Controller和Action
		if (isset($this->controller_name)) {
			define('APP_CONTROLLER', ucfirst($this->controller_name));
		} else {
			define('APP_CONTROLLER', isset($_GET['c']) ? ucfirst($_GET['c']) : 'Index');
		}
		if (isset($this->action_name)) {
			define('APP_ACTION', $this->action_name);
		} else {
			define('APP_ACTION', isset($_GET['a']) ? $_GET['a'] : 'index');
		}
	}
	
	/**
	 * 自动载入
	 * @param string $class_name
	 * @throws Exception
	 */
	public function autoload($class_name) {
		$core_class = array('Controller', 'Model', 'Page', 'View');
		$class_path = '';
		
		if (in_array($class_name, $core_class)) { // 核心类
			$class_path = SHARPPHP_PATH.'/'.$class_name.'.class.php';
		} elseif (substr($class_name, -10) == 'Controller') { // 控制器
			$class_path = APP_PATH.'/Controller/'.APP_MODULE.'/'.$class_name.'.class.php';
		} elseif (substr($class_name, -5) == 'Model') { // 模型
			$class_path = APP_PATH.'/Model/'.$class_name.'.class.php';
		} else { // 外部类
			$class_path = APP_PATH.'/Model/'.$class_name.'.class.php';
			if (!file_exists($class_path)) {
				$files = scandir(APP_PATH.'/Model/');
				foreach ($files as $file) {
					if ($file == '.' || $file == '..') {
						continue;
					}
					if (!is_dir(APP_PATH.'/Model/'.$file)) {
						continue;
					}
					$class_path = APP_PATH.'/Model/'.$file.'/'.$class_name.'.class.php';
					if (file_exists($class_path)) {
						break;
					}
				}
			}
		}
		
		if (!file_exists($class_path)) {
			throw new Exception("SharpPHP: class $class_name file not found!");
		}
		include $class_path;
		if (!class_exists($class_name)) {
			throw new Exception("SharpPHP: class $class_name not found!");
		}
	}
	
	/**
	 * 创建PDO对象
	 * @return PDO
	 */
	private function createPDO() {
		$config_db = $this->getConfig('db');
		$dsn = "mysql:host={$config_db['host']};dbname={$config_db['name']}";
		
		$pdo = new PDO($dsn, $config_db['user'], $config_db['pass'], $config_db['options']);
		
		$this->pdo = $pdo;
		Model::$sharpphp = $this;
		return $pdo;
	}
	
	/**
	 * 创建View对象
	 * @return View
	 */
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
	
	/**
	 * 创建Colltroller对象
	 * @throws Exception
	 * @return Colltroller
	 */
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
	
	/**
	 * 处理请求
	 */
	public function run() {
		$action_name = APP_ACTION.'Action';
		$action_name = method_exists($this->controller, $action_name) ? $action_name : 'notFoundAction';
		
		$this->controller->{$action_name}($this->view);
	}
	
	/**
	 * 生成Model的工程类,产生代码提示
	 * @param string $dirpath
	 */
	public function initModelFactory($dirpath=null) {
		$dirpath = isset($dirpath) ? $dirpath : APP_PATH.'/Model';
		$model_names = $this->getModelNames($dirpath);
		$factory_func_code = '';
		foreach ($model_names as $model_name) {
			$factory_func_code .= $this->factoryCode($model_name) . "\n\n";
		}
	
		$model_class_code = <<<CODE
<?php
if (!defined('SharpPHP')) { exit(); }

class M {
	private static \$objs = array();
	
$factory_func_code
}
CODE;
		file_put_contents($dirpath.'/M.class.php', $model_class_code);
	
		echo "Create Model Factory OK!";
	}
	
	/**
	 * 读取所有Model的类名
	 * @param unknown $dirpath
	 * @return Ambigous <multitype:, multitype:string >
	 */
	private function getModelNames($dirpath) {
		$model_names = array();
		$ext = '.class.php';
		$files = scandir($dirpath);
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			$sub_dirname = $dirpath.'/'.$file;
			if (is_dir($sub_dirname)) {
				$model_names = array_merge($model_names, $this->getModelNames($sub_dirname));
			} elseif (substr($file, -10) === $ext) {
				$model_names[] = substr($file, 0, -10);
			}
		}
		return $model_names;
	}
	
	/**
	 * Model工程方法实现
	 * @param string $model_name
	 * @return string
	 */
	private function factoryCode($model_name) {
		return <<<CODE
	/**
	 * @return $model_name
	 */
	public static function get$model_name() {
		if (isset(self::\$objs['$model_name'])) {
			return self::\$objs['$model_name'];
		}
		\$model = self::\$objs['$model_name'] = new $model_name();
		return \$model;
	}
CODE;
	}
}