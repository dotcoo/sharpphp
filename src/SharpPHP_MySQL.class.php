<?php
/**
 * SharpPHP_MySQL class
 * @author slin zhao
 * @link http://www.sharpphp.org
 */
class SharpPHP_MySQL {
	public $host = '127.0.0.1';
	public $user = 'root';
	public $pass = '123456';
	public $name = 'test';
	public $charset = 'utf8';
	public $prefix = '';
	public $page = 1;
	public $pagesize = 15;
	public $debug = false;
	public $showsql = false;
	public $conn = null;
	public $rs = null;
	public $sqlformat = null;
	public $sql = null;
	public $sqlcount = null;

	function __construct() {
		$_ENV['db'] = $this;
	}

	public function init($db) {
		$this->host = $db['host'];
		$this->user = $db['user'];
		$this->pass = $db['pass'];
		$this->name = $db['name'];
		$this->charset = $db['charset'];
		$this->prefix = $db['prefix'];
		$this->connect();
	}
	
	public function initPrarm($host, $user, $pass, $name, $charset = 'utf8', $prefix = '') {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
		$this->charset = $charset;
		$this->prefix = $prefix;
		$this->connect();
	}
	
	public function connect() {
		$this->page = (empty($_GET['page']) || intval($_GET['page']) < 1) ? 1 : intval($_GET['page']);
		$this->conn = mysql_connect($this->host, $this->user, $this->pass);
		mysql_select_db($this->name, $this->conn);
		mysql_set_charset($this->charset, $this->conn);
	}

	public function __clone() {
		$this->conn = mysql_connect($this->host, $this->user, $this->pass, true);
	}

	// select config
	public function vsprintf($format, $ps) {
		$format = str_replace('?', "'%s'", $format);
		$ps = is_array($ps) ? $ps : array($ps);
		$flen = strlen($format);
		$sql = '';
		$pi = 0;
		for ($i = 0; $i < $flen; $i++) {
			if ($format{$i} == '%' && $format{$i + 1} != '%') {
				$i++;
				switch ($format{$i}) {
					case 'a':
						var_export($ps[$pi]);
						$sql .= "'" . implode("', '", $ps[$pi]) . "'";
						break;
					case 'd':
						$sql .= $ps[$pi];
						break;
					case 'h':
						$sql .= $this->where($ps[$pi], 'HAVING');
						break;
					case 's':
						$sql .= $ps[$pi];
						break;
					case 't':
						$sql .= '`' . $this->prefix . $ps[$pi] . '`';
						break;
					case 'w':
						$sql .= $this->where($ps[$pi]);
						break;
					default:
						$sql .= $format{$i};
				}
				$pi++;
			} else {
				$sql .= $format{$i};
			}
		}
		return $sql;
	}

	public function where($wheres, $prefix = 'WHERE') {
		if (!is_array($wheres)) {
			exit("$prefix not array()");
		}
		$wheresqls = array();
		foreach ($wheres as $sql=>$ps) {
			$sql = is_numeric($sql) ? $ps : $sql;
			$wheresqls[] = $this->vsprintf($sql, $ps);
		}
		return count($wheresqls) > 0 ? " $prefix " . implode(' AND ', $wheresqls) : '';
	}

	public function group($str, $having = array()) {
		$this->sql .= " GROUP BY $str";
		$this->sql .= empty($having) ? '' : $this->where($having, 'HAVING');
		return $this;
	}

	public function order($str) {
		$this->sql .= " ORDER BY $str";
		return $this;
	}

	public function limit($limit, $offset) {
		$this->sql .= " LIMIT $limit, $offset";
		return $this;
	}

	public function page($page, $pagesize = 0) {
		$pagesize = empty($pagesize) ? $this->pagesize : $pagesize;
		$limit = ($page - 1) * $pagesize;
		return $this->limit($limit, $pagesize);
	}

	public function forupdate() {
		$this->sql .= " FOR UPDATE";
		return $this;
	}

	// transaction
	public function begin() {
		return $this->query('BEGIN');
	}

	public function commit() {
		return $this->query('COMMIT');
	}

	public function rollback() {
		return $this->query('ROLLBACK');
	}

	// sql execute
	public function query($sql = '') {
		$this->sqlcount = $this->sql = empty($sql) ? $this->sql : $sql;
		if ($this->showsql) {
			exit($this->sql);
		}
		$this->rs = mysql_query($this->sql);
		if (mysql_errno()) {
			if ($this->debug) {
				echo $this->sql, '<br>', mysql_error(), '<br>';
			}
			exit('sql error!');
		}
		return $this;
	}

	public function free_result() {
		mysql_free_result($this->rs);
		$this->rs = null;
		return $this;
	}

	public function insert_id() {
		return mysql_insert_id($this->conn);
	}

	// get data
	public function fetch_all($col = null) {
		$this->query();
		if (empty($this->rs) || !is_resource($this->rs)) {
			exit('fetch_all, result is null or result is not resource!');
		}
		$rows = array();
		while ($row = mysql_fetch_assoc($this->rs)) {
			if (empty($col)) {
				$rows[] = $row;
			} else {
				$rows[$row[$col]] = $row;
			}
		}
		$this->free_result();
		return $rows;
	}

	public function fetch_col($key, $col = null) {
		$this->query();
		if (empty($this->rs) || !is_resource($this->rs)) {
			exit('fetch_all, result is null or result is not resource!');
		}
		if (empty($col)) {
			$col = $key;
			$key = null;
		}
		$arr = array();
		while ($row = mysql_fetch_assoc($this->rs)) {
			if (empty($key)) {
				$arr[] = $row[$col];
			} else {
				$arr[$row[$key]] = $row[$col];
			}
		}
		$this->free_result();
		return $arr;
	}

	public function fetch_array() {
		$this->query();
		if (empty($this->rs) || !is_resource($this->rs)) {
			exit('fetch_array, result is null or result is not resource!');
		}
		$row = mysql_fetch_assoc($this->rs);
		$this->free_result();
		return $row;
	}

	public function fetch_one($col = 0) {
		$this->query();
		if (empty($this->rs) || !is_resource($this->rs)) {
			exit('fetch_one, result is null or result is not resource!');
		}
		$val = mysql_result($this->rs, $col);
		$this->free_result();
		return $val;
	}

	public function fetch_page($pk = null, $order = null, $page = 0, $pagesize = 0) {
		if (!empty($order)) {
			$this->order($order);
		}
		$page = empty($page) ? $this->page : $page;
		$pagesize = empty($pagesize) ? $this->pagesize : $pagesize;
		$rows = $this->page($page, $pagesize)->fetch_all($pk);
		$rowcount = $this->count();
		return array($rows, $rowcount);
	}

	public function count() {
		$this->sqlcount = preg_replace('/^SELECT (.*?) FROM/i', 'SELECT count(*) FROM', $this->sqlcount);
		$this->sqlcount = str_replace('%', '%%', $this->sqlcount);
		return $this->query($this->sqlcount)->fetch_one();
	}

	// sharp method
	public function select($table, $wheres = array(), $col = '*') {
		$ps = array($col, $table, $wheres);
		$this->sql = $this->vsprintf("SELECT %s FROM %t%w", $ps);
		return $this;
	}

	public function insert($table, $data) {
		$cols = $vals = array();
		foreach ($data as $col=>$val) {
			$cols[] = "`$col`";
			$vals[] = $val;
		}
		$ps = array($table, implode(', ', $cols), implode("', '", $vals));
		$this->sql = $this->vsprintf("INSERT INTO %t (%s) VALUES('%s')", $ps);
		return $this->query()->insert_id();
	}

	public function update($table, $data, $wheres) {
		if (empty($wheres)) {
			exit('UPDATE not found where!');
		}
		$sets = array();
		foreach ($data as $col=>$val) {
			$sets[] = sprintf("%s = '%s'", $col, $val);
		}
		$ps = array($table, implode(', ', $sets), $wheres);
		$this->sql = $this->vsprintf("UPDATE %t SET %s%w", $ps);
		return $this->query();
	}

	public function delete($table, $wheres) {
		if (empty($wheres)) {
			exit('DELETE not found where!');
		}
		$ps = array($table, $wheres);
		$this->sql = $this->vsprintf("DELETE FROM %t%w", $ps);
		return $this->query();
	}

	public function plus($table, $wheres, $col, $val = 1, $safe = true) {
		if (empty($wheres)) {
			exit('PLUS not found where!');
		}
		$ps = array($table, $col, $col, $val, $wheres);
		if ($safe) {
			return $this->query("UPDATE %t SET %s = last_insert_id(%s + %d)%w", $ps)->insert_id();
		} else {
			return $this->query("UPDATE %t SET %s = %s + %d%w", $ps)->select($table, $wheres, $col)->fetch_one();
		}
	}

	// tools
	public function pagebar($count, $page = 0, $pagesize = 0) {
		$page = empty($page) ? $this->page : $page;
		$pagesize = empty($pagesize) ? $this->pagesize : $pagesize;
		$pagination = new SharpPHP_Pagination();
		return $pagination->init($count, $page, $pagesize)->show();
	}
}