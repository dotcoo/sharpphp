<?php
if (!defined('SharpPHP')) { exit(); }

/* 分页样式
<style>
.pagebar {text-align:center;}
.pagebar span, .pagebar a {border:1px #DDD solid;margin:2px 2px;display:inline-block;width:40px;height:24px;line-height:24px;}
.pagebar .long {width:80px;}
.pagebar a {color:black;text-decoration:none;}
.pagebar span.current {color:#999;}
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
	public $half = 2;					// 平均显示数量
	public $url;						// url地址
	public $params = array();			// GET参数
	
	/**
	 * Page分页组件
	 * @param number $total
	 * @param number $pagesize
	 * @param number $page
	 */
	function __construct($total, $pagesize=15, $page=null) {
		$this->total = $total;
		$this->page = empty($page) ? $_GET['page'] : $page;
		$this->pagesize = $pagesize;
	}
	
	/**
	 * 每页的链接地址
	 * @param number $page
	 * @return string
	 */
	public function link($page) {
		$this->params['page'] = $page;
		$query = http_build_query($this->params);
		return "$this->url?$query";
	}
	
	/**
	 * 显示分页代码
	 * @return string
	 */
	public function show() {
		if (empty($this->total)) {
			return '<div class="pagebar">暂无数据!</div>';
		}
		
		$urls = parse_url($_SERVER['REQUEST_URI']);
		$this->url = $urls['path'];
		parse_str(isset($urls['query'])?$urls['query']:'', $this->params);
		
		$this->last = intval(($this->total+$this->pagesize-1)/$this->pagesize);
		$this->page = $this->page < 1 ? 1 : $this->page;
		$this->page = $this->page > $this->last ? $this->last : $this->page;
		
		$len = $this->half * 2 + 1;

		$html = '<div class="pagebar">';
		// 上一页
		if ($this->page == 1) {
			$html .= '<span class="long current">'.$this->prev_text.'</span>';
		} else {
			$html .= '<a href="'.$this->link($this->page-1).'" class="long">'.$this->prev_text.'</a>';
		}
		
		if ($this->last > $len) {
			$start = $this->page - $this->half;
			if ($this->page - $this->half < 1) {
				$start = 1;
			}
			if ($this->page + $this->half > $this->last) {
				$start = $this->last + 1 - $len;
			}
			
			// 第一页
			if ($this->page - $this->half > 1) {
				$html .= '<a href="'.$this->link(1).'">1</a>';
			}
			if ($this->page - $this->half > 2) {
				$html .= '...';
			}
		} else {
			$start = 1;
		}
		
		for ($i=0, $list=$start+$i; $i<$len &&  $list<= $this->last; $i++, $list++) {
			if ($list == $this->page) { 
				$html .= '<span class="current">'.$list.'</span>';
			} else {
				$html .= '<a href="'.$this->link($list).'">'.$list.'</a>';
			}
		}
		
		if ($this->last > $len) {
			// 最后一夜
			if ($this->last - $this->half - 1 > $this->page) {
				$html .= '...';
			}
			if ($this->last - $this->half > $this->page) {
				$html .= '<a href="'.$this->link($this->last).'">'.$this->last.'</a>';
			}
		}
		// 下一页
		if ($this->page == $this->last) {
			$html .= '<span class="long current">'.$this->next_text.'</span>';
		} else {
			$html .= '<a href="'.$this->link($this->page+1).'" class="long">'.$this->next_text.'</a>';
		}
		$html .= '</div>';
		return $html;
	} 
}