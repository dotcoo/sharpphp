<?php
if (!defined('SharpPHP')) { exit(); }

/* 分页样式
<style>
.pagebar {text-align:center;}
.pagebar span, .pagebar a {border:1px #DDD solid;margin:1px;display:inline-block;padding:4px;}
.pagebar a {color:black;text-decoration:none;}
.pagebar span {color:#999;}
</style>
*/

/**
 * SharpPHP Page class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class Page {
	public $total;						// 记录总条数
	public $page;						// 当前第几页
	public $pagesize;					// 每页大小
	public $last;						// 总共页数
	public $prev_text = '&lt;上一页';		// 上一页
	public $next_text = '下一页&gt;';		// 下一页
	public $half = 4;					// 平均显示数量
	// 	public $url;						// url地址
	// 	public $params = array();			// GET参数

	/**
	 * Page分页组件
	 * @param number $total
	 * @param number $pagesize
	 * @param number $page
	*/
	function __construct($total, $pagesize=15, $page=1) {
		$this->total = $total;
		$this->page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : $page;
		$this->pagesize = $pagesize;
	}

	/**
	 * 显示分页代码
	 * @return string
	 */
	public function show($params = array()) {
		if (empty($this->total)) {
			return '<div class="pagebar">暂无数据!</div>';
		}

		$urls = parse_url($_SERVER['REQUEST_URI']);
		if (empty($params)) {
			if (isset($urls['query'])) {
				parse_str($urls['query'], $params);
			}
		}
		if (isset($params)) {
			unset($params['page']);
		}
		$url = $urls['path'].'?'.http_build_query($params).(empty($params)?'':'&');

		$this->last = intval(($this->total+$this->pagesize-1)/$this->pagesize);
		$this->page = $this->page < 1 || $this->page > $this->last ? 1 : $this->page;
		$start = $this->page - $this->half;
		$end = $this->page + $this->half;
		$length = $this->half * 2;
		
		if ($start < 1) {
			$start = 1;
			$end = $start+$length < $this->last ? $start+$length : $this->last;
		}
		if ($end > $this->last) {
			$end = $this->last;
			$start = $end-$length > 1 ? $end-$length : 1;
		}
		echo "1, $start, $end, $this->last";
		$html = '<div class="pagebar">';
		
		// 上一页
		if ($this->page == 1) {
			$html .= '<span>首页</span>';
			$html .= '<span>上一页</span>';
		} else {
			$html .= '<a href="'.$url.'page=1">首页</a>';
			$html .= '<a href="'.$url.'page='.($this->page-1).'">上一页</a>';
		}
		
		// 页数
		for ($i=$start; $i<=$end; $i++) {
			if ($this->page == $i) {
				$html .= '<span>'.$i.'</span>';
			} else {
				$html .= '<a href="'.$url.'page='.$i.'">'.$i.'</a>';
			} 
		}
		
		// 下一页
		if ($this->page == $this->last) {
			$html .= '<span>下一页</span>';
			$html .= '<span>尾页</span>';
		} else {
			$html .= '<a href="'.$url.'page='.($this->page+1).'">下一页</a>';
			$html .= '<a href="'.$url.'page='.$this->last.'">尾页</a>';
		}
		
		$html .= '</div>';
		return $html;
	}
}