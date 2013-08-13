<?php
/**
 * 获取表单数据
 * @param string $names
 * @return array
 */
function getForm($names, &$form=null) {
	$form = isset($form) ? $form : $_REQUEST;
	$names = explode(',', $names);
	// $names = array_map('trim', $names);
	$data = array();
	foreach ($names as $name) {
		$data[$name] = $form[$name];
	}
	return $data;
}

/**
 * 获取二维数组的指定列，返回一个一维数组
 * @param array $rows
 * @param array $key
 * @return array
 */
function rowsGetField(&$rows, $key) {
	$arr = array();
	foreach($rows as $row) {
		$arr[] = $row[$key];
	}
	return $arr;
}

/**
 * 将当前二维数组，转换为一个键值对数组
 * @param array $rows
 * @param array $key
 * @param string $val
 * @return array
 */
function rowsGetAssoc(&$rows, $key, $val=null) {
// 	if (function_exists('array_column')) {
// 		return array_column(&$rows, $key, $val);
// 	}
	$arr = array();
	if (isset($val)) {
		foreach($rows as $row) {
			$arr[$row[$key]] = $row[$val];
		}
	} else {
		foreach($rows as $row) {
			$arr[$row[$key]] = $row;
		}
	}
	return $arr;
}

/**
 * 对树结构递归，禅城层次
 * @param array $rows
 * @param array $tree
 * @param number $pid
 * @param string $key
 * @param string $pkey
 */
function rowsToTree(&$rows, &$tree, $pid=0, $key="id", $pkey="pid") {
	$trees = array();
	foreach ($rows as &$row) {
		if($pid == $row[$pkey]) {
			// 			echo $row['pid'], '|', $row['id'], "\n";
			rowsToTree($rows, $row, $row[$key]);
			$trees[] = $row;
		}
	}
	$tree['tree'] = $trees;
}

/**
 * 对树结构排序，添加floor层次
 * @param array $rows
 * @param array $tree
 * @param number $floor
 * @param number $pid
 * @param string $key
 * @param string $pkey
 */
function rowsToFloor(&$rows, &$tree, &$floor=0, $pid=0, $key="id", $pkey="pid") {
	$floor ++;
	$trees = array();
	foreach ($rows as &$row) {
		if($pid == $row[$pkey]) {
			$row2 = $row;
			$row2['floor'] = 0 + $floor;
			$tree[] = $row2;
			rowsToFloor($rows, $tree, $floor, $row[$key]);
		}
	}
	$floor --;
}

/**
 * 获得ip地址
 * @return number
 */
function ip(){
	return ip2long($_SERVER['REMOTE_ADDR']);
}

/**
 * 检测是否上传上传文件
 * @param string $name file标签的name属性
 * @param number $i 多文件上传时的索引
 */
function is_upload($name, $index=null){
	if (!(isset($_SERVER['HTTP_CONTENT_TYPE']) && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data')===0)) {
		return array(false, 'Upload: form error!<br />enctype="multipart/form-data"');
	}
	if (empty($_FILES[$name])) {
		return array(false, "Upload: form $name not exist!");
	}
	if ($index===null) {
		if ($_FILES[$name]['error'] !== 0) {
			return array(false, "Upload: upload error, code {$_FILES[$name]['error']}");
		}
	} else {
		if (!isset($_FILES[$name][$index])) {
			return array(false, "Upload: form $name index not exist");
		}
		if ($_FILES[$name]['error'][$index] !== 0) {
			return array(false, "Upload: upload error, code {$_FILES[$name]['error']}");
		}
	}
	return array(true, 'Upload: OK!');
}

/**
 * 获取扩展名
 * @param string $file 文件名
 * @return string
 */
function extname($file){
	return strtolower(pathinfo($file, PATHINFO_EXTENSION));
}

/**
 * 随机字符串
 * @param number $len
 * @return string
 */
function random($len) {
	$char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$str = '';
	for ($i=0; $i<$len; $i++) {
		$str .= $char{mt_rand(0, 63)};
	}
	return $str;
}

/**
 * 计算当前天数
 * @return number
 */
function today() {
	return (int) (($_SERVER['REQUEST_TIME'] + 28800) / 86400);
}