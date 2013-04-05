<?php
if (!defined('SharpPHP')) { exit(); }

/**
 * SharpPHP Controller class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class Controller {
	public $module;
	public $action;
	
	/**
	 * 没有找到action
	 * @param View $view
	 */
	public function notFoundAction($view) {
		$view->message('not found Action');
	}
	
	/**
	 * 是否GET请求
	 * @return boolean
	 */
	public function isGet() {
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}
	
	/**
	 * 是否POST请求
	 * @return boolean
	 */
	public function isPost() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	/**
	 * 是否Ajax请求
	 * @return boolean
	 */
	public function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}
	
	/**
	 * 获取表单数据
	 * @param string $names
	 * @return array
	 */
	public function getForm($names) {
		$names = explode(',', $names);
		$data = array();
		foreach ($names as $name) {
			$data[$name] = $_REQUEST[$name];
		}
		return $data;
	}
}