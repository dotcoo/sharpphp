<?php
if (!defined('SharpPHP')) { exit(); }

/**
 * SharpPHP Model class
 * @author dotcoo zhao
 * @link https://github.org/dotcoo/sharpphp
 */
class Model {
	public static $sharpphp;
	
	public $pdo;					// pdo对象
	public $table_name;				// 表名
	public $prefix = '';			// 表前缀
	public $full_table_name;		// 完整表名
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
	function __construct($table_name, $pk=null, $prefix=null, $pdo=null) {
		$this->table_name = $table_name;
		$this->pk = $pk;
		$this->prefix = $prefix;
		$this->pdo = $pdo;
		
		$this->initConfig();
	}
	
	/**
	 * 初始化配置
	 */
	public function initConfig($config_model=null) {
		if (isset($config_model)) {
    	} elseif (self::$sharpphp instanceof SharpPHP) {
    		$config_model = self::$sharpphp->getConfig('model');
    	} else {
    		$config_model = array();
    	}
    	
		$default_model = array(
				'prefix' => 'sharp_',	// 表前缀
				'pk' => 'id', 			// 表主键
				'pagesize' => 15, 		// 默认分页数
		);
		$config_model = array_merge($default_model, $config_model);
		$this->setConfig($config_model);
		
		// 设置属性
		$this->prefix = isset($this->prefix) ? $this->prefix : $config_model['prefix'];
		$this->full_table_name = $this->prefix . $this->table_name;
		$this->pk = isset($this->pk) ? $this->pk : $config_model['pk'];
		$this->pagesize = $config_model['pagesize'];
		if (self::$sharpphp instanceof SharpPHP) {
			$this->pdo = self::$sharpphp->getPDO();
		}
	}
	
	/**
	 * 获取Model配置
	 * @param string $name 配置名称
	 * @return string|array
	 */
	public function getConfig($name=null) {
		if (isset($name)) {
			return isset($this->config[$name]) ? $this->config[$name] : '';
		} else {
			return $this->config;
		}
	}
	
	/**
	 * 设置Model配置
	 * @param string $name 配置名称
	 * @param string $value 配置值
	 */
	public function setConfig($name, $value=null) {
		if (isset($value)) {
			$this->config[$name] = $value;
		} else {
			$this->config = $name;
		}
		if (self::$sharpphp instanceof SharpPHP) {
			self::$sharpphp->setConfig('model', $this->getConfig());
		}
	}
	
	/**
	 * 获取PDO对象
	 * @return PDO
	 */
	public function getPDO() {
		return $this->pdo;
	}
	
	/**
	 * 设置PDO
	 * @param PDO $pdo
	 */
	public function setPDO($pdo) {
		$this->pdo = $pdo;
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

	// SQL Query
	
	/**
	 * 执行sql语句
	 * @param string $sql
	 * @param array $params
	 * @throws Exception
	 * @return Model
	 */
	public function query($sql=null, $params=null) {
		$sql = is_null($sql) ? $this->sql : $sql;
		$params = is_null($params) ? $this->params : $params;
		$this->sqls[] = array($sql, $params);
		
		$this->result = $this->getPDO()->prepare($sql);
		if (false === $this->result) {
			throw new Exception('Model: PDO prepare error. errorCode: ' . $this->getPDO()->errorCode() . '.');
		}
		
		$len = count($params);
		for($i=0; $i<$len; $i++) {
			$ok = $this->result->bindValue($i+1, $params[$i]);
			if (false === $ok) {
				throw new Exception('Model: PDO bindValue error. errorCode: ' . $this->getPDO()->errorCode() . '.');
			}
		}
		
		$ok = $this->result->execute();
		if (false === $ok) {
			throw new Exception('Model: PDO execute error. errorCode: ' . $this->getPDO()->errorCode() . '.');
		}
		
		return $this;
	}

	/**
	 * 释放结果集
	 * @return Model
	 */
	public function freeResult() {
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
	public function insertId() {
		return $this->getPDO()->lastInsertId();
	}

	/**
	 * 影响的行数
	 * @return number
	 */
	public function rowCount() {
		return $this->result->rowCount();
	}
	
	// SQL Transaction
	
	/**
	 * 开始事务
	 * @return boolean
	 */
	public function begin() {
		return $this->getPDO()->beginTransaction();
	}
	
	/**
	 * 提交数据
	 * @return boolean
	 */
	public function commit() {
		return $this->getPDO()->commit();
	}
	
	/**
	 * 回滚数据
	 * @return boolean
	 */
	public function rollBack() {
		return $this->getPDO()->rollBack();
	}

	// Model Method
	
	/**
	 * 添加数据
	 * @param array $data
	 * @return Model
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
		return $this->query();
	}
	
	/**
	 * 更新数据
	 * @param array $where
	 * @param array $data
	 * @throws Exception
	 * @return Model
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
		return $this->query();
	}
	
	/**
	 * 删除数据
	 * @param array $where
	 * @throws Exception
	 * @return Model
	 */
	public function delete($where) {
		if (empty($where)) {
			throw new Exception('Model: DELETE not found where!');
		}
		$this->params = array();
		$this->sql = "DELETE FROM `{$this->full_table_name}`";
		$this->sql .= $this->where($where);
		return $this->query();
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
	
	// Model Fetch
	
	/**
	 * 获取结果集中的数据
	 * @param string $col
	 * @return array
	 */
	public function fetchAll($col = null, $fetch_style = PDO::FETCH_ASSOC) {
		if($this->result === null){
			$this->query();
		}
		$rows = $this->result->fetchAll($fetch_style);
		$rows_assoc = array();
		if($col != null){
			foreach ($rows as $row) {
				$rows_assoc[$row[$col]] = $row;
			}
		}
		$this->freeResult();
		return $col == null ? $rows : $rows_assoc;
	}
	
	/**
	 * 获取结果集中的一列
	 * @param string $key
	 * @param string $col
	 * @return array
	 */
	public function fetchCol($key, $col = null) {
		if($this->result === null){
			$this->query();
		}
		if (empty($col)) {
			$col = $key;
			$key = null;
		}
		$rows = $row = $this->result->fetchAll(PDO::FETCH_ASSOC);
		$arr = array();
		if (empty($key)) {
			foreach ($rows as $row) {
				$arr[] = $row[$col];
			}
		} else {
			foreach ($rows as $row) {
				$arr[$row[$key]] = $row[$col];
			}
		}
		$this->freeResult();
		return $arr;
	}
	
	/**
	 * 获取结果集中的第一行
	 * @param number $fetch_style
	 * @return array
	 */
	public function fetchRow($fetch_style = PDO::FETCH_ASSOC) {
		$this->limit(1);
		if($this->result === null){
			$this->query();
		}
		$row = $this->result->fetch($fetch_style);
		$this->freeResult();
		return $row;
	}
	
	/**
	 * 获取结果集中的第一行第一列
	 * @param number $col
	 * @return string
	 */
	public function fetchOne($col = 0) {
		$this->limit(1);
		if($this->result === null){
			$this->query();
		}
		$row = $this->result->fetch(PDO::FETCH_NUM);
		$val = $row[$col];
		$this->freeResult();
		return $val;
	}
	
	/**
	 * 获取符合条件的行数
	 * @param array $where
	 * @return string
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
	 * @return Model
	 */
	public function incr($where, $col, $val = 1) {
		if (empty($where)) {
			throw new Exception('Model: PLUS not found where!');
		}
		$this->params = array($val);
		$this->sql = "UPDATE `{$this->full_table_name}` SET `$col` =  last_insert_id(`$col` + ?)";
		$this->sql .= $this->where($where);
		return $this->query();
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
	 * @return Model
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
	
	/**
	 * 获取外键数据
	 * @param array $rows
	 * @param string $key
	 * @return array
	 */
	public function foreignKey(&$rows, $key='id', $field='*') {
		$ids = array();
		foreach($rows as $row) {
			$ids[] = $row[$key];
		}
		return $this->select(array("`{$this->pk}` in (#)" => array($ids)), $field)->fetchAll($this->pk);
	}
}