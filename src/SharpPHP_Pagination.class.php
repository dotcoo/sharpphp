<?php
/**
 * SharpPHP_Pagination class
 * @author slin zhao
 * @link http://www.sharpphp.org
 */
class SharpPHP_Pagination {
	public $current = 1;
	public $first = 1;
	public $last;
	public $prev;
	public $next;
	
	public $first_text = '首页';
	public $last_text = '尾页';
	public $prev_text = '上一页';
	public $next_text = '下一页';
	
	public $max = true;
	
	public $pagesize = 15;
	public $sum;
	public $half = 4;
	
	public $start;
	public $end;
	
	function __construct() {}
	
	public function init($sum, $page, $pagesize) {
		$this->sum = $sum;
		$this->current = $page;
		$this->pagesize = $pagesize;
		return $this;
	}
	
	public function calc() {
		$this->last = intval(($this->sum + $this->pagesize - 1) / $this->pagesize);
		
		$this->current = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
		if($this->current > $this->last)
			$this->current = $this->last;
		
		$this->prev = $this->current - 1;
		if($this->prev < $this->first)
			$this->prev = $this->current;
			
		$this->next = $this->current + 1;
		if($this->next > $this->last)
			$this->next = $this->current;
		
		$left = $this->current - $this->half;
		$right = $this->current + $this->half;
		
		if($left >= $this->first && $right <= $this->last) {
			$this->start = $left;
			$this->end = $right;
		} elseif($left <= $this->first && $right >= $this->last) {
			$this->start = $this->first;
			$this->end = $this->last;
		} elseif($left < $this->first && $right < $this->last) {
			$this->start = $this->first;
			$this->end = $right + ($this->first - $left);
			if($this->end > $this->last)
				$this->end = $this->last;
		} elseif($left > $this->first &&  $right > $this->last) {
			$this->end = $this->last;
			$this->start = $left - ($right - $this->last);
			if($this->start < $this->first)
				$this->start = $this->first;
		}
	}
	
	public function link($page) {
		$link = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['SCRIPT_NAME'];
		$params = $_GET;
		$params['page'] = $page;
		$ps = http_build_query($params);
		return "$link?$ps";
	}
	
	public function firstpage() {
		if($this->current == $this->first) {
			return '<span>' . $this->first_text . '</span>';
		} else {
			return '<a href="' . $this->link($this->first) . '">' . $this->first_text . '</a>';
		}
	}
	
	public function lastpage() {
		if($this->current == $this->last) {
			return '<span>' . $this->last_text . '</span>';
		} else {
			return '<a href="' . $this->link($this->last) . '">' . $this->last_text . '</a>';
		}
	}
	
	public function prevpage() {
		if($this->current == $this->prev) {
			return '<span>' . $this->prev_text . '</span>';
		} else {
			return '<a href="' . $this->link($this->prev) . '">' . $this->prev_text . '</a>';
		}
	}
	
	public function nextpage() {
		if($this->current == $this->next) {
			return '<span>' . $this->next_text . '</span>';
		} else {
			return '<a href="' . $this->link($this->next) . '">' . $this->next_text . '</a>';
		}
	}
	
	public function show() {
		$this->calc();
		
		if(empty($this->sum)) {
			return '暂无内容';
		}
		$str = '<div class="pagebar">';
		if($this->max)
			$str .= $this->firstpage();
		$str .= $this->prevpage();
		for($page = $this->start; $page <= $this->end; $page++) {
			if($page == $this->current) {
				$str .= '<span class="acurrent">' . $page . '</span>';
			} else {
				$str .= '<a href="' . $this->link($page) . '">' . $page . '</a>';
			}
		}
		$str .= $this->nextpage();
		if($this->max)
			$str .= $this->lastpage();
		$str .= '</div>';
		
		return $str;
	}
}