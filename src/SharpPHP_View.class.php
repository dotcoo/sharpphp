<?php
/**
 * SharpPHP_View class
 * @author slin zhao
 * @link http://www.sharpphp.org
 */
class SharpPHP_View {
	public $tplpath;
	public $outpath;
	
	function __construct() {
		$_ENV['v'] = $this;
	}
	
	public function init($tplpath, $outpath) {
		$this->tplpath = $tplpath;
		$this->outpath = $outpath;
	}
	
	// 模版最终路径
	public function view($file = '') {
		$file = empty($file) ? substr($_SERVER['SCRIPT_NAME'], 1, -4) : $file;
		$outfile = $this->outpath.'/'.$file.'.php';
		$tplfile = $this->tplpath.'/'.$file.'.htm';
		if(!file_exists($outfile) || filemtime($tplfile)>filemtime($outfile)){
			$html = $this->parse($file);
			$outdir = dirname($outfile);
			if(!file_exists($outdir)){
				mkdir($outdir, 0700, true);
			}
			file_put_contents($outfile, $html);
		}
		return $outfile;
	}
	
	// 模版分析
	public function parse($file){
		$file = is_array($file)?$file[1]:$file;
		$tplfile = $this->tplpath.'/'.$file.'.htm';
		if(!file_exists($tplfile)){
			exit("view $tplfile not found!");
		}
		$html = file_get_contents($tplfile);
		
		$html = preg_replace_callback('/<!--\s*{view ([^}]+)}\s*-->/', array($this, 'parse'), $html);
		$html = preg_replace('/<!--\s*{if ([^}]+)}\s*-->/', '<?php if($1){ ?>', $html);
		$html = preg_replace('/<!--\s*{elseif ([^}]+)}\s*-->/', '<?php }elseif($1){ ?>', $html);
		$html = preg_replace('/<!--\s*{else}\s*-->/', '<?php }else{ ?>', $html);
		$html = preg_replace('/<!--\s*{\/[^}]+}\s*-->/', '<?php } ?>', $html);
		$html = preg_replace('/<!--\s*{loop (\S+) (\S+) (\S+)}\s*-->/', '<?php foreach($1 as $2 => $3){ ?>', $html);
		$html = preg_replace('/<!--\s*{eval ([^}]+)}\s*-->/', '<?php $1 ?>', $html);
		
		$html = preg_replace('/{(\$[^}]+)}/', '<?php echo $1; ?>', $html);
		$html = preg_replace('/{=([^}]+)}/', '<?php echo $1; ?>', $html);
		//$html = preg_replace('/{(\w+) ([^}]+)}/', '<?php echo $v->tag_$1($2); ? >', $html); //自动标签
		
		return $html;
	}
}