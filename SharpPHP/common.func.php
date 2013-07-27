<?php
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
	if (function_exists('array_column')) {
		return array_column(&$rows, $key, $val);
	}
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
			// 			echo $row['pid'], '|', $row['id'], "\n";
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
	$ipstr = isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
	return ip2long($ipstr);
}

/**
 * 检测是否上传上传文件
 * @param string $name file标签的name属性
 * @param number $i 多文件上传时的索引
 */
function is_upload($name, $i=-1){
	if(substr($_SERVER['CONTENT_TYPE'], 0, 19) !== 'multipart/form-data'){
		throw new Exception("Upload: upload file not found!<br />enctype=\"multipart/form-data\"");
	}
	if(empty($_FILES[$name])){
		return false;
	}
	$file_error = $i<0 ? $_FILES[$name]['error'] : $_FILES[$name]['error'][$i];
	$isupload = $file_error == 0;
	if($file_error != 0 && $file_error != 4){
		throw new Exception("upload error! code: $file_error");
	}
	return $isupload;
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