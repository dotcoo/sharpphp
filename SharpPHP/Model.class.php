<?php
if (!defined('SharpPHP')) { exit(); }

/**
 * SharpPHP Model class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class Model {
	public static $global_pdo;		// 全局pdo对象
	public static $global_prefix;	// 全局表前缀
	
	public $pdo;					// 私有pdo对象
	public $table_name;				// 表名
	public $prefix = '';			// 表前缀
	public $full_table_name;		// 表全名
	public $pk;						// 主键
	public $sql = '';				// 准备执行的sql
	public $params = array();		// 参数
	public $sqls = array(); 		// 已经执行的sql
	public $result;					// 结果集
	
	/**
	 * Model Construct
	 * @param string $table_name
	 * @param string $pk
	 * @param string $prefix
	 * @param PDO $pdo
	 */
	function __construct($table_name, $pk="id", $prefix=null, $pdo=null) {
		$this->table_name = $table_name;
		$this->prefix = is_null($prefix) ? self::$global_prefix : $prefix;
		$this->full_table_name = $this->prefix . $this->table_name;
		$this->pk = $pk;
		$this->pdo = is_null($pdo) ? self::$global_pdo : $pdo;
	}
	
	/**
	 * Model扩展类型
	 * @param string $model_sql
	 * @param array $model_params
	 * @throws Exception
	 * @return string
	 */
	public function vsprintf($model_sql, $model_params) {
		$sql = '';
		$model_params = is_array($model_params) ? $model_params : array($model_params);
		$len = strlen($model_sql);
		for ($i = 0, $mpi=0; $i < $len; $i++) {
			switch ($model_sql{$i}) {
				case '?':
					$sql .= '?';
					$this->params[] = $model_params[$mpi];
					$mpi++;
					break;
				case '#':
					if(!is_array($model_params[$mpi])){
						throw new Exception("Model: \"$model_sql\" param not array!");
					}
					$sql .= str_repeat('?,', count($model_params[$mpi]));
					$sql{strlen($sql)-1} = ' ';
					$this->params = array_merge($this->params, $model_params[$mpi]);
					$mpi++;
					break;
				default:
					$sql .= $model_sql{$i};
			}
		}
		return $sql;
	}
	
	// sql method
	
	/**
	 * sql查询条件
	 * @param array $wheres
	 * @param string $prefix
	 * @return string
	 */
	public function where($wheres, $prefix = 'WHERE') {
		if (!is_array($wheres)) {
			$wheres = array($wheres);
		}
		$where_sqls = array();
		foreach ($wheres as $sql=>$ps) {
			$sql = is_numeric($sql) ? $ps : $sql;
			$where_sqls[] = $this->vsprintf($sql, $ps);
		}
		return count($where_sqls) > 0 ? " $prefix " . implode(' AND ', $where_sqls) : '';
	}

	/**
	 * sql分组
	 * @param string $col
	 * @param array $having
	 * @return Model
	 */
	public function group($col, $having = array()) {
		$this->sql .= " GROUP BY $col";
		$this->sql .= empty($having) ? '' : $this->where($having, 'HAVING');
		return $this;
	}

	/**
	 * sql排序
	 * @param string $col
	 * @return Model
	 */
	public function order($col) {
		$this->sql .= " ORDER BY $col";
		return $this;
	}

	/**
	 * 数据偏移
	 * @param number $offset
	 * @param number $limit
	 * @return Model
	 */
	public function limit($offset, $limit=null) {
		if ($limit == null) {
			$limit = $offset;
			$offset = 0;
		}
		$this->sql .= " LIMIT $offset, $limit";
		return $this;
	}

	/**
	 * sql分页
	 * @param number $page
	 * @param number $pagesize
	 * @return Model
	 */
	public function page($page, $pagesize = 0) {
		$pagesize = empty($pagesize) ? $this->pagesize : $pagesize;
		$limit = ($page - 1) * $pagesize;
		return $this->limit($limit, $pagesize);
	}

	/**
	 * 排它锁
	 * @return Model
	 */
	public function forUpdate() {
		$this->sql .= " FOR UPDATE";
		return $this;
	}
	
	/**
	 * 共享锁
	 * @return Model
	 */
	public function lockInShareMode() {
		$this->sql .= " LOCK IN SHARE MODE";
		return $this;
	}

	// SQL Execute
	
	/**
	 * 执行sql语句
	 * @param string $sql
	 * @param array $params
	 * @throws Exception
	 * @return boolean
	 */
	public function execute($sql=null, $params=null) {
		$sql = is_null($sql) ? $this->sql : $sql;
		$params = is_null($params) ? $this->params : $params;
		$this->sqls[] = array($sql, $params);
		
		$this->result = $this->pdo->prepare($sql);
		if (false === $this->result) {
			var_dump($this->pdo->errorInfo());
			throw new Exception('Model: PDO prepare error, errorCode ' . $this->pdo->errorCode() . '.');
		}
		
		$len = count($params);
		for($i=0; $i<$len; $i++) {
			$this->result->bindValue($i+1, $params[$i]);
		}
		
		$ok = $this->result->execute();
		if (false === $ok) {
			var_dump($this->result->errorInfo());
			throw new Exception('Model: PDO execute error, errorCode ' . $this->pdo->errorCode() . '.');
		}
		
		return $ok;
	}

	/**
	 * 释放结果集
	 * @return Model
	 */
	public function free_result() {
		if ($this->result instanceof PDOStatement) {
			$this->result->closeCursor();
		}
		$this->result = null;
		return $this;
	}

	/**
	 * 获得自增id的指
	 * @return number
	 */
	public function insert_id() {
		return $this->pdo->lastInsertId();
	}
	
	// SQL Transaction
	
	/**
	 * 开始事务
	 * @return boolean
	 */
	public function begin() {
		return $this->pdo->beginTransaction();
	}
	
	/**
	 * 提交数据
	 * @return boolean
	 */
	public function commit() {
		return $this->pdo->commit();
	}
	
	/**
	 * 回滚数据
	 * @return boolean
	 */
	public function rollBack() {
		return $this->pdo->rollBack();
	}

	// Model Method
	
	/**
	 * 添加数据
	 * @param array $data
	 * @return number
	 */
	public function insert($data) {
		$cols = $vals = array();
		$this->params = array();
		foreach ($data as $col=>$val) {
			$cols[] = "`$col`";
			$vals[] = '?';
			$this->params[] = $val;
		}
		$cols = implode(', ', $cols);
		$vals = implode(', ', $vals);
		$this->sql = "INSERT INTO `{$this->full_table_name}` ($cols) VALUES($vals)";
		return $this->execute()->insert_id();
	}
	
	/**
	 * 查询数据
	 * @param array $where
	 * @param string $field
	 * @return Model
	 */
	public function select($where=null, $field='*') {
		$this->params = array();
		$this->sql = "SELECT $field FROM `{$this->full_table_name}`";
		if (!empty($where)) {
			$this->sql .= $this->where($where);
		}
		return $this;
	}
	
	/**
	 * 更新数据
	 * @param array $where
	 * @param array $data
	 * @throws Exception
	 * @return boolean
	 */
	public function update($where, $data) {
		if (empty($where)) {
			throw new Exception('Model: UPDATE not found where!');
		}
		$this->params = array();
		$sets = array();
		foreach ($data as $col=>$val) {
			$sets[] = "`$col` = ?";
			$this->params[] = $val;
		}
		$this->sql = "UPDATE `{$this->full_table_name}` SET " . implode(', ', $sets);
		$this->sql .= $this->where($where);
		return $this->execute();
	}
	
	/**
	 * 删除数据
	 * @param array $where
	 * @throws Exception
	 * @return boolean
	 */
	public function delete($where) {
		if (empty($where)) {
			throw new Exception('Model: DELETE not found where!');
		}
		$this->params = array();
		$this->sql = "DELETE FROM `{$this->full_table_name}`";
		$this->sql .= $this->where($where);
		return $this->execute();
	}
	
	// Model Fetch
	
	/**
	 * 获取结果集中的数据
	 * @param string $col
	 * @return array
	 */
	public function fetch_all($col = null) {
		if($this->result === null){
			$this->execute();
		}
		$rows = array();
		while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
			if (empty($col)) {
				$rows[] = $row;
			} else {
				$rows[$row[$col]] = $row;
			}
		}
		$this->free_result();
		return $rows;
	}
	
	/**
	 * 获取结果集中的一列
	 * @param string $key
	 * @param string $col
	 * @return array
	 */
	public function fetch_col($key, $col = null) {
		if($this->result === null){
			$this->execute();
		}
		if (empty($col)) {
			$col = $key;
			$key = null;
		}
		$arr = array();
		while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
			if (empty($key)) {
				$arr[] = $row[$col];
			} else {
				$arr[$row[$key]] = $row[$col];
			}
		}
		$this->free_result();
		return $arr;
	}
	
	/**
	 * 获取结果集中的第一行
	 * @param number $fetch_style
	 * @return array
	 */
	public function fetch_row($fetch_style = PDO::FETCH_ASSOC) {
		if($this->result === null){
			$this->execute();
		}
		$row = $this->result->fetch($fetch_style);
		$this->free_result();
		return $row;
	}
	
	/**
	 * 获取结果集中的第一行第一列
	 * @param number $col
	 * @return string
	 */
	public function fetch_one($col = 0) {
		if($this->result === null){
			$this->execute();
		}
		$row = $this->result->fetch(PDO::FETCH_NUM);
		$val = $row[$col];
		$this->free_result();
		return $val;
	}
	
	/**
	 * 获取符合条件的行数
	 * @param array $where
	 * @return number
	 */
	public function count($where = array()) {
		return $this->select($where, 'count(*)')->fetch_one();
	}
	
	/**
	 * 将选中行的指定字段加一
	 * @param array $where 
	 * @param string $col
	 * @param int $val
	 * @param bool $safe
	 * @throws Exception
	 * @return int
	 */
	public function plus($where, $col, $val = 1, $safe=false) {
		if (empty($where)) {
			throw new Exception('Model: PLUS not found where!');
		}
		$this->params = array($val);
		if ($safe) {
			$this->sql = "UPDATE `{$this->full_table_name}` SET `$col` = `$col` + ?";
		} else {
			$this->sql = "UPDATE `{$this->full_table_name}` SET `$col` =  last_insert_id(`$col` + ?)";
		}
		$this->sql .= $this->where($where);
		$this->execute();
		
		return $this->select($where, $col)->fetch_one();
	}
	
	/**
	 * 根据主键查找行
	 * @param number $id
	 * @return array
	 */
	public function find($id) {
		$where = array("`{$this->pk}` = ?" => $id);
		return $this->select($where)->fetch_row();
	}
	
	/**
	 * 保存数据,自动判断是新增还是更新
	 * @param array $data
	 * @return number|boolean
	 */
	public function save($data) {
		if (empty($data[$this->pk])) {
			return $this->insert($data);
		} else {
			$where = array("`{$this->pk}` = ?" => $data[$this->pk]);
			unset($data[$this->pk]);
			return $this->update($where, $data);
		}
	}
}