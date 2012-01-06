<?php
/**
 * SharpPHP_Image class
 * @author slin zhao
 * @link http://www.sharpphp.org
 */
class SharpPHP_Image {
	public $code_len = 4;
	public $code_char_list = '23456789abcdefghijklmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
	public $code_expire = 180;
	
	function __construct(){
		
	}
	
	// 生成验证码图片
	public function verifycode(){
		header('Content-type: image/png');
		// 创建图片
		$dst_image = imagecreatetruecolor(90, 30);
		// 背景颜色
		$back_color = imagecolorallocate($dst_image, 255, 255, 255);
		// 设置背景
		imagefill($dst_image, 0, 0, $back_color); //背景
		// 字体颜色
		$font_color = imagecolorallocate($dst_image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
		// 选择字体
		$font_file = SP_ROOT.'/data/ttfs/t'.mt_rand(1, 13).'.ttf';
		// 随即验证码
		$char_len = strlen($this->code_char_list)-1;
		$code = '';
		for($i=0; $i<$this->code_len; $i++){
			$n = mt_rand(0, $char_len);
			$code .= $this->code_char_list{$n};
		}
		$_SESSION['verifycode'] = array('code'=>$code, 'time'=>time()+$this->code_expire, 'count'=>0);
		// 图形文字
		$x = 3;
		$y = 23;
		$code_a = str_split($code);
		foreach($code_a as $c){
			$font_du = mt_rand(0, 90);
			$font_du = $font_du<=45 ? $font_du : 270+$font_du;
			imagettftext($dst_image, 20, $font_du, $x+intval($font_du<=45?5:-5), $y, $font_color, $font_file, $c);
			$x += 20;
		}
		// 输出
		imagepng($dst_image);
	}
	
	// 检查验证码
	public function imgcodecheck($imgcode, $del=true){
		$_SESSION['imgcodecount']++;
		
		$code = $_SESSION['imgcode'];
		$time = $_SESSION['imgcodetime'];
		$count = $_SESSION['imgcodecount'];
		
		if($count > $_ENV['ep']->config['base']['imgcodetime']){
			return false;
		}
		if(empty($time) || time()>$time+$_ENV['ep']->config['base']['imgcodetime']){
			return false;
		}
		if($del){
			$_SESSION['imgcode'] = '';
		}
		return !empty($code) && $code === strtolower($imgcode);
	}
	
	// 缩略图片
	public function zoomOutImage($infile, $outfile, $new_w, $new_h, $in=true){
		if(!file_exists($infile)){
			exit('源图片不存在');
		}
	
		$imgcreatefunc = array(1=>'imagecreatefromgif', 'imagecreatefromjpeg', 'imagecreatefrompng');
		$imgsavefunc = array(1=>'imagegif', 'imagejpeg', 'imagepng');
		list($src_w, $src_h, $src_type) = getimagesize($infile);
		$src_image = $imgcreatefunc[$src_type]($infile) or exit('图片的类型不能识别!');
	
		if($src_w > $new_w || $src_h > $new_h){
			$scale_w = $new_w / $src_w;
			$scale_h = $new_h / $src_h;
			$b = $in ? ($scale_w < $scale_h) : !($scale_w < $scale_h);
			$scale = ($b) ? $scale_w : $scale_h;
			$zoom_w = $src_w * $scale;
			$zoom_h = $src_h * $scale;
		}else{
			$zoom_w = $src_w;
			$zoom_h = $src_h;
		}
	
		$dst_image = @imagecreatetruecolor($zoom_w, $zoom_h);
		imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $zoom_w, $zoom_h, $src_w, $src_h);
	
		$imgsavefunc[$src_type]($dst_image, $outfile);
		imagedestroy($dst_image);
		imagedestroy($src_image);
	}
	
	// 放大图片
	public function zoomInImage($infile, $outfile, $new_w, $new_h){
		if(!file_exists($infile)){
			exit('源图片不存在');
		}
	
		$imgcreatefunc = array(1=>'imagecreatefromgif', 'imagecreatefromjpeg', 'imagecreatefrompng');
		$imgsavefunc = array(1=>'imagegif', 'imagejpeg', 'imagepng');
		list($src_w, $src_h, $src_type) = getimagesize($infile);
		$src_image = $imgcreatefunc[$src_type]($infile) or exit('图片的类型不能识别!');
	
		if($src_w < $new_w || $src_h < $new_h){
			$scale_w = $new_w / $src_w;
			$scale_h = $new_h / $src_h;
			$scale = ($scale_w > $scale_h) ? $scale_w : $scale_h;
			$zoom_w = $src_w * $scale;
			$zoom_h = $src_h * $scale;
		}else{
			$zoom_w = $src_w;
			$zoom_h = $src_h;
		}
		
		$dst_image = @imagecreatetruecolor($zoom_w, $zoom_h);
		imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $zoom_w, $zoom_h, $src_w, $src_h);
		
		$imgsavefunc[$src_type]($dst_image, $outfile);
		imagedestroy($dst_image);
		imagedestroy($src_image);
	}
	
	// 裁剪图片
	public function cutImage($infile, $outfile, $width, $height, $x=-1, $y=-1){
		if(!file_exists($infile)){ exit('源图片不存在'); }
		
		$imgcreatefunc = array(1=>'imagecreatefromgif', 'imagecreatefromjpeg', 'imagecreatefrompng');
		$imgsavefunc = array(1=>'imagegif', 'imagejpeg', 'imagepng');
		list($src_w, $src_h, $src_type) = getimagesize($infile);
		
		$x = $x>=0 ? $x : intval(($src_w-$width)/2);
		$y = $y>=0 ? $y : intval(($src_h-$height)/2);
		
		$src_image = $imgcreatefunc[$src_type]($infile) or exit('图片的类型不能识别!');
	
		$dst_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($dst_image, $src_image, 0, 0, $x, $y, $width, $height, $width, $height);
		
		$imgsavefunc[$src_type]($dst_image, $outfile);
		
		imagedestroy($dst_image);
		imagedestroy($src_image);
	}
}