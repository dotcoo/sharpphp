<?php
if (!defined('SharpPHP')) { exit(); }

/**
 * SharpPHP View class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class View {
	public $sharpphp;
	
	public $tplpath;			// 模板目录
	public $outpath;			// 输出目录
	public $name;				// 模板名称
	public $config = array();	// 配置信息
	public $data = array();		// 绑定数据
	
	public $extension;			// 模板文件后缀
	public $content_type;		// 模板mime类型
	public $check;				// 检车模板更新
	public $message_tpl;		// 消息模板文件
	
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
     * 初始化配置
     */
    public function initConfig($config_view=null) {
    	if (isset($config_view)) {
    	} elseif ($this->sharpphp instanceof SharpPHP) {
    		$config_view = $this->sharpphp->getConfig('view');
    	} else {
    		$config_view = array();
    	}
    	
    	$default_view = array(
				'extension' => '.htm',							// 模板扩展名
				'content_type' => 'text/html; charset=utf-8', 	// 页面mime类型
				'check' => true, 								// 检查模板更新
				'message_tpl' => '', 					// 消息模板名称
    	);
    	$config_view = array_merge($default_view, $config_view);
    	$this->setConfig($config_view);
    	
    	// 设置属性
    	$this->extension = $config_view['extension'];
    	$this->prefix = $config_view['content_type'];
    	$this->check = $config_view['check'];
    	$this->message_tpl = $config_view['message_tpl'];
    }
    
    /**
     * 获取配置
     * @param string $name
     * @return string
     */
    public function getConfig($name=null) {
    	if (isset($name)) {
    		return isset($this->config[$name]) ? $this->config[$name] : '';
    	} else {
    		return $this->config;
    	}
    }
    
    /**
     * 设置配置
     * @param string $name
     * @param string $value
     */
    public function setConfig($name, $value=null) {
    	if (isset($value)) {
			$this->config[$name] = $value;
		} else {
			$this->config = $name;
		}
    	if ($this->sharpphp instanceof SharpPHP) {
    		$this->sharpphp->setConfig('view', $this->getConfig());
    	}
    }
    
    /**
     * 执行模板
     * @param string $__name
     */
    public function display($__name='', $content_type=null) {
    	$__name = empty($__name) ? $this->name : $__name;
    	header('Content-Type: '.(isset($content_type)?$content_type:$this->content_type));
    	extract($this->data);
    	require $this->path($__name);
    }
	
	/**
	 * 模板路径
	 * @param string $name
	 * @throws Exception
	 * @return string
	 */
	private function path($name = null) {
		$name = isset($name) ? $name : $this->name;
		$outfile = $this->outpath.'/'.$name.'.php';
		$tplfile = $this->tplpath.'/'.$name.$this->extension;
		if ($this->check && (!file_exists($outfile) || filemtime($outfile)<filemtime($tplfile))) {
			if(!file_exists($tplfile)){
				throw new Exception("View: template file $tplfile not found!");
			}
			$html = $this->parse(file_get_contents($tplfile));
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
	private function parse($html){
		$html = preg_replace_callback('/<!-- {include ([^}]+)} -->/', array($this, 'includes'), $html);
		$html = preg_replace('/<!-- {if ([^}]+)} -->/', '<?php if (\1) { ?>', $html);
		$html = preg_replace('/<!-- {elseif ([^}]+)} -->/', '<?php } elseif (\1) { ?>', $html);
		$html = preg_replace('/<!-- {else} -->/', '<?php } else { ?>', $html);
		$html = preg_replace('/<!-- {\/[^}]+} -->/', '<?php } ?>', $html);
		$html = preg_replace('/<!-- {loop (\S+) (\S+) (\S+)} -->/', '<?php foreach (\1 as \2 => \3) { ?>', $html);
		
		$html = preg_replace_callback('/{=([^}]+)}/', array($this, 'pipe'), $html);
		$html = preg_replace('/{(\$[^}]+)}/', '<?php echo \1; ?>', $html);
		$html = preg_replace('/{:([^}]+)}/', '<?php \1; ?>', $html);
		//$html = preg_replace('/{(\w+) ([^}]+)}/', '<?php echo $v->tag_\1(\2); ? >', $html); //自动标签
		
		return $html;
	}
	
	/**
	 * 模板嵌套
	 * @param string $file
	 * @throws Exception
	 * @return string
	 */
	private function includes($file) {
		$file = is_array($file)?$file[1]:$file;
		$tplfile = $this->tplpath.'/'.$file.$this->extension;
		if(!file_exists($tplfile)){
			throw new Exception("View: $tplfile not found!");
		}
		return $this->parse(file_get_contents($tplfile));
	}
	
	/**
	 * 管道处理
	 * @param array $m
	 * @return string
	 */
	private function pipe ($m) {
		$pipe_tpl = $m[1];
		if (strpos($pipe_tpl, '|') === false) {
			return "<?php echo $pipe_tpl; ?>";
		}
	
		$pipes = explode('|', $pipe_tpl);
		$length = count($pipes);
		$code = $pipes[0];
	
		for ($i=1; $i<$length; $i++) {
			$pipe = $pipes[$i];
			if (strpos($pipe, '(') === false) {
				$code = $pipe.'('.$code.')';
			} else {
				list($func, $params) = explode('(', $pipe, 2);
				$params{strrpos($params, ')')} = ' ';
				$params = explode(',', $params);
				$params_new = array();
				foreach ($params as $param) {
					if ($param === '') {
						$params_new[] = $code;
					} else {
						$params_new[] = $param;
					}
				}
				$code = $func.'('.implode(',', $params_new).')';
			}
		}
		return "<?php echo $code; ?>";
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
		if (!empty($this->message_tpl)) {
			$this->info = $info;
			$this->url = $url;
			$this->sec = $sec;
			$this->display($this->message_tpl);
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
	public function json($info, $status=0, $data=array()) {
		$data['info'] = $info;
		$data['status'] = $status;
		if (!defined('JSON_UNESCAPED_UNICODE')) {
			define('JSON_UNESCAPED_UNICODE', null);
		}
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
}