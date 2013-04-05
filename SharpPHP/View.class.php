<?php
if (!defined('SharpPHP')) { exit(); }

/**
 * SharpPHP View class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class View {
	public $tplpath;			// 模板目录
	public $outpath;			// 输出目录
	public $name;				// 模板名称
	public $data = array();		// 绑定数据
	public $check = true;		// 检查更新
	public $message_name;		// 消息模板
	
	/**
	 * 试图类
	 * @param string $tplpath
	 * @param string $outpath
	 * @param string $name
	 */
	function __construct($tplpath, $outpath, $name) {
		$this->tplpath = $tplpath;
		$this->outpath = $outpath;
		$this->name = $name;
	}
	
	/**
	 * 设置变量
	 * @param string $name
	 * @param mixed $val
	 */
 	public function __set($name, $val) {
        $this->data[$name] = $val;
    }

    /**
     * 获取变量
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * 变量是否存在
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * 删除变量
     * @param string $name
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }
    
    /**
     * 执行模板
     * @param string $_view_name_
     */
    public function display($_view_name_='') {
    	$_view_name_ = empty($_view_name_) ? $this->name : $_view_name_;
    	extract($this->data);
    	include $this->path($_view_name_);
    }
	
	/**
	 * 模板路径
	 * @param string $name
	 * @throws Exception
	 * @return string
	 */
	public function path($name = '') {
		$name = empty($name) ? $this->name : $name;
		$outfile = $this->outpath.'/'.$name.'.php';
		$tplfile = $this->tplpath.'/'.$name.'.htm';
		if ($this->check && (!file_exists($outfile) || filemtime($outfile)<filemtime($tplfile))) {
			if(!file_exists($tplfile)){
				throw new Exception("View: $tplfile not found!");
			}
			return $this->parse(file_get_contents($tplfile));
			$html = $this->parse($name);
			$outdir = dirname($outfile);
			if(!file_exists($outdir)){
				mkdir($outdir, 0700, true);
			}
			file_put_contents($outfile, $html);
		}
		return $outfile;
	}
	
	/**
	 * 模版解析
	 * @param string $html
	 * @return string
	 */
	public function parse($html){
		$html = preg_replace_callback('/{include ([^}]+)}/', array($this, 'includes'), $html);
		$html = preg_replace('/{if ([^}]+)}/', '<?php if (\1) { ?>', $html);
		$html = preg_replace('/{elseif ([^}]+)}/', '<?php } elseif (\1) { ?>', $html);
		$html = preg_replace('/{else}/', '<?php } else { ?>', $html);
		$html = preg_replace('/{\/[^}]+}/', '<?php } ?>', $html);
		$html = preg_replace('/{loop (\S+) (\S+) (\S+)}/', '<?php foreach (\1 as \2 => \3) { ?>', $html);
		
		$html = preg_replace('/{(\$[^}]+)}/', '<?php echo \1; ?>', $html);
		$html = preg_replace('/{=([^}]+)}/', '<?php echo \1; ?>', $html);
		$html = preg_replace('/{|([^}]+)}/', '<?php \1; ?>', $html);
		//$html = preg_replace('/{(\w+) ([^}]+)}/', '<?php echo $v->tag_\1(\2); ? >', $html); //自动标签
		
		return $html;
	}
	
	/**
	 * 模板嵌套
	 * @param string $file
	 * @throws Exception
	 * @return string
	 */
	public function includes($file) {
		$file = is_array($file)?$file[1]:$file;
		$tplfile = $this->tplpath.'/'.$file.'.htm';
		if(!file_exists($tplfile)){
			throw new Exception("View: $tplfile not found!");
		}
		return $this->parse(file_get_contents($tplfile));
	}
	
	/**
	 * 网址跳转
	 * @param string $url
	 */
	public function redirect($url) {
		header("Location: $url", true, 302);
		exit();
	}
	
	/**
	 * 显示消息
	 * @param string $info
	 * @param string $url
	 * @param number $sec
	 */
	public function message($info, $url=null, $sec=3) {
		$sec = $sec * 1000;
		if (!empty($this->message_name)) {
			$this->info = $info;
			$this->url = $url;
			$this->sec = $sec;
			$this->display($this->message_name);
			exit();
		}
		
		$script = '';
		if (is_null($url)) {
			$script = "<script>setTimeout(function(){history.back();}, $sec);</script>";
		} else {
			$script = "<script>setTimeout(function(){location.href='$url';}, $sec);</script>";
		}
		$message = <<< MSG
<!DOCTYPE html>
<html>
<head>
	$script
</head>
<body>
	<div>$info</div>
</body>
</html>
MSG;
		echo $message;
		exit();
	}
	
	/**
	 * JSON数据
	 * @param string $info
	 * @param number $status
	 * @param array $data
	 */
	public function json($info, $status=0, &$data=array()) {
		$data['info'] = $info;
		$data['status'] = $status;
		if (!defined('JSON_UNESCAPED_UNICODE')) {
			define('JSON_UNESCAPED_UNICODE', null);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
}