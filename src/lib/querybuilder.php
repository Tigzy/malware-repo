<?php

class QueryUpdate
{
	private $name;
	private $value;
	private $type;		// text, int, field
			
	public function __construct($name, $value, $type = 'text') 
	{
		$this->name 		= $name;
		$this->value 		= $value;
		$this->type 		= $type;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function getType() {
		return $this->type;
	}
}

class QueryLimit
{
	private $offset;
	private $count;
	
	public function __construct($offset = -1, $count = -1) 
	{
		$this->setLimits($offset, $count);
	}
	
	public function setLimits($offset, $count)
	{
		$this->offset = $offset;
		$this->count  = $count;
	}
	
	public function getOffset() {
		return $this->offset;
	}
	
	public function getCount() {
		return $this->count;
	}
}

class QueryWhere
{
	private $left;
	private $operator;	// =, LIKE
	private $right;
	private $type;		// text, int, field
			
	public function __construct($left, $right, $operator = '=', $type = 'text') 
	{
		$this->left 		= $left;
		$this->right 		= $right;
		$this->operator 	= $operator;
		$this->type 		= $type;
	}
	
	public function getLeft() {
		return $this->left;
	}
	
	public function getOperator() {
		return $this->operator;
	}
	
	public function getRight() {
		return $this->right;
	}
	
	public function getType() {
		return $this->type;
	}
}

class QueryOrderBy
{
	private $name;
	private $sort;
	private $is_field;
	
	public function __construct($name, $sort, $is_field = True) 
	{
		$this->name = $name;
		$this->sort = $sort;
		$this->is_field = $is_field;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getSort() {
		return $this->sort;
	}
	
	public function isField() {
		return $this->is_field;
	}
}

class QueryTable
{
	private $name;
	private $select 	= array();		// Associative array ('key' => 'alias')
	private $delete		= false;		// True/False
	private $update     = array();		// Associative aray ('key' => 'value')
	private $insert     = array();		// Associative array ('key' => 'value')
	private $rawselect  = array();		// Associative array, used for RAW data (COUNT(*), etc...)
	private $where 		= array();
	private $rawwhere   = array();
	private $orderby 	= array();
	private $groupby    = array();
	private $joinwhere  = array();		// Only valid for join table
	private $jointype	= 'INNER';		// Only valid for join table
	
	public function __construct($name) 
	{
		$this->name = $name;
	}
	
	public function setSelect(array $select) {
		$this->select = $select;
	}
	
	public function setDelete($delete) {
		$this->delete = $delete;
	}
	
	public function setUpdate(array $update) {
		$this->update = $update;
	}
	
	public function setInsert(array $insert) {
		$this->insert = $insert;
	}
	
	public function setRawSelect(array $select) {
		$this->rawselect = $select;
	}
	
	public function setRawWhere(array $where) {
		$this->rawwhere = $where;
	}
	
	public function setJoinType($jointype) {
		$this->jointype = $jointype;
	}
	
	public function addSelect($name, $select) {
		$this->select[$name] = $select;
	}
	
	public function addUpdate(QueryUpdate $update) {
		array_push($this->select, $update);
	}
	
	public function addInsert(QueryUpdate $insert) {
		array_push($this->insert, $insert);
	}
	
	public function addRawSelect($name, $select) {
		$this->rawselect[$name] = $select;
	}
	
	public function addRawWhere($where) {
		array_push($this->rawwhere, $where);
	}
	
	public function setOrderBy(array $orderby) {
		$this->orderby = $orderby;
	}
	
	public function setGroupBy(array $groupby) {
		$this->groupby = $groupby;
	}
	
	public function addOrderBy(QueryOrderBy $orderby) {
		array_push($this->orderby, $orderby);
	}
	
	public function addGroupBy($groupby) {
		array_push($this->groupby, $groupby);
	}
	
	public function setWhere(array $where) {
		$this->where = $where;
	}
	
	public function addWhere(QueryWhere $where) {
		array_push($this->where, $where);
	}
	
	public function setJoinWhere($joinwhere) {
		$this->joinwhere = $joinwhere;
	}
	
	public function addJoinWhere(QueryWhere $where) {
		array_push($this->joinwhere, $where);
	}
	
	public function getSelects() {
		return $this->select;
	}
	
	public function getDelete() {
		return $this->delete;
	}
	
	public function getUpdates() {
		return $this->update;
	}
	
	public function getInserts() {
		return $this->insert;
	}
	
	public function getRawSelects() {
		return $this->rawselect;
	}
	
	public function getRawWheres() {
		return $this->rawwhere;
	}
	
	public function getWhere() {
		return $this->where;
	}
	
	public function getOrderBy() {
		return $this->orderby;
	}
	
	public function getGroupBy() {
		return $this->groupby;
	}
	
	public function getJoinWhere() {
		return $this->joinwhere;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getJoinType() {
		return $this->jointype;
	}
}

class QueryBuilder
{
	private $tables 	= array();				// Main tables
	private $joins 		= array();				// Joined tables
	private $limit 		= null;					// Limits
	
	public function __construct() 
	{
		$this->limit = new QueryLimit();
	}
	
	public function __destruct() 
	{
		
	}
	
	public function setLimits($offset, $count) {
		$this->limit->setLimits($offset, $count);
	}
	
	public function addTable(QueryTable $table) {
		array_push($this->tables, $table);
	} 
	
	public function addJoinTable(QueryTable $table) {
		array_push($this->joins, $table);
	} 
	
	public function build()
	{
		foreach ($this->tables as $table){
			if ($table->getDelete()) 			 return $this->buildDelete();
			if (count($table->getSelects()) > 0) return $this->buildSelect();
			if (count($table->getUpdates()) > 0) return $this->buildUpdate();
			if (count($table->getInserts()) > 0) return $this->buildInsert();
		}
		return '';
	}
	
	private function updateBlock()
	{
		$query = "UPDATE ";
		$is_first = true;
		$ref_table = '';
		foreach ($this->tables as $table){			
			$query = $query . ($is_first ? "" : ", ") . $table->getName() . " " . $table->getName();
			if ($is_first) $ref_table = $table->getName();
			$is_first = false;
		}		
		foreach ($this->joins as $table){			
			$query = $query . " " . $table->getJoinType() . " JOIN " . $table->getName() . " " . $table->getName();
			$wheres = $table->getJoinWhere();
			$is_first = true;
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " ON " : " AND ") . $table->getName() . "." . $where->getLeft() . " " . $where->getOperator() . " ";
				if ($where->getType() == 'int') 		$query = $query . $where->getRight();
				elseif ($where->getType() == 'field') 	$query = $query . $where->getRight();
				else 									$query = $query . "'" . $where->getRight() . "'";
				$is_first = false;
			}
		}
		return $query;
	}
	
	private function insertBlock()
	{
		$query = "INSERT INTO ";
		$is_first = true;
		$ref_table = '';
		foreach ($this->tables as $table){			
			$query = $query . ($is_first ? "" : ", ") . $table->getName();
			if ($is_first) $ref_table = $table->getName();
			$is_first = false;
		}
		return $query;
	}
	
	private function setBlock()
	{
		// Build Set
		$query = "";
		$is_first = true;
		foreach ($this->tables as $table){
			$updates = $table->getUpdates();			
			foreach ($updates as $update) {
				$query = $query . ($is_first ? "SET " : ", ") . $table->getName() . "." . $update->getName() . " = ";
				if ($update->getType() == 'int') 		$query = $query . $update->getValue();
				elseif ($update->getType() == 'field') 	$query = $query . $update->getValue();
				else 									$query = $query . "'" . $update->getValue() . "'";
				$is_first = false;
			}	
		}
		foreach ($this->joins as $table){
			$updates = $table->getUpdates();	
			foreach ($updates as $update) {
				$query = $query . ($is_first ? "SET " : ", ") . $table->getName() . "." . $update->getName() . " = ";
				if ($update->getType() == 'int') 		$query = $query . $update->getValue();
				elseif ($update->getType() == 'field') 	$query = $query . $update->getValue();
				else 									$query = $query . "'" . $update->getValue() . "'";
				$is_first = false;
			}
		}
		return $query;
	}
	
	private function valuesBlock()
	{
		// Build Set
		$query = "";
		$is_first = true;
		$has_elements = false;
		foreach ($this->tables as $table){
			$inserts = $table->getInserts();			
			foreach ($inserts as $insert) {
				$query = $query . ($is_first ? "(" : ", ") . $insert->getName();
				$is_first = false;
				$has_elements = true;
			}
		}
		if ($has_elements) $query = $query . ")";
		$is_first = true;
		foreach ($this->tables as $table){
			$inserts = $table->getInserts();			
			foreach ($inserts as $insert) {
				$query = $query . ($is_first ? " VALUES (" : ", ");
				if ($insert->getType() == 'int') 		$query = $query . $insert->getValue();
				elseif ($insert->getType() == 'field') 	$query = $query . $insert->getValue();
				else 									$query = $query . "'" . $insert->getValue() . "'";
				$is_first = false;
			}	
		}
		if ($has_elements) $query = $query . ")";
		return $query;
	}
	
	private function deleteBlock()
	{
		$query = "DELETE ";
		return $query;
	}
	
	private function selectBlock()
	{
		// Build select
		$query = "SELECT ";
		$is_first = true;
		foreach ($this->tables as $table){
			$selects = $table->getSelects();			
			foreach ($selects as $select => $alias) {
				$query = $query . ($is_first ? "" : ", ") . $table->getName() . "." . $select . (empty($alias) ? "" : " as " . $alias);
				$is_first = false;
			}	
			$selects = $table->getRawSelects();			
			foreach ($selects as $select => $alias) {
				$query = $query . ($is_first ? "" : ", ") . $select . (empty($alias) ? "" : " as " . $alias);
				$is_first = false;
			}	
		}
		foreach ($this->joins as $table){
			$selects = $table->getSelects();	
			foreach ($selects as $select => $alias) {
				$query = $query . ($is_first ? "" : ", ") . $table->getName() . "." . $select . (empty($alias) ? "" : " as " . $alias);
				$is_first = false;
			}
			$selects = $table->getRawSelects();			
			foreach ($selects as $select => $alias) {
				$query = $query . ($is_first ? "" : ", ") . $select . (empty($alias) ? "" : " as " . $alias);
				$is_first = false;
			}
		}
		return $query;
	}
	
	private function fromBlock($aliases = True)
	{
		// Build From
		$query = "FROM ";
		$is_first = true;
		$ref_table = '';
		foreach ($this->tables as $table){			
			$query = $query . ($is_first ? "" : ", ") . $table->getName() . ($aliases ? " " . $table->getName() : "");
			if ($is_first) $ref_table = $table->getName();
			$is_first = false;
		}		
		foreach ($this->joins as $table){			
			$query = $query . " " . $table->getJoinType() . " JOIN " . $table->getName() . " " . $table->getName();
			$wheres = $table->getJoinWhere();
			$is_first = true;
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " ON " : " AND ") . $table->getName() . "." . $where->getLeft() . " " . $where->getOperator() . " ";
				if ($where->getType() == 'int') 		$query = $query . $where->getRight();
				elseif ($where->getType() == 'field') 	$query = $query . $where->getRight();
				else 									$query = $query . "'" . $where->getRight() . "'";
				$is_first = false;
			}
		}
		return $query;
	}
	
	private function whereBlock($aliases = True)
	{
		// Build Where
		$query = '';
		$is_first = true;
		foreach ($this->tables as $table){
			$wheres = $table->getWhere();
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " WHERE " : " AND ") . ($aliases ? $table->getName() . "." : "") . $where->getLeft() . " " . $where->getOperator() . " ";
				if ($where->getType() == 'int') 		$query = $query . $where->getRight();
				elseif ($where->getType() == 'field') 	$query = $query . $where->getRight();
				else 									$query = $query . "'" . $where->getRight() . "'";
				$is_first = false;
			}	
			$wheres = $table->getRawWheres();
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " WHERE " : " AND ") . $where;
				$is_first = false;
			}
		}
		foreach ($this->joins as $table){
			$wheres = $table->getWhere();
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " WHERE " : " AND ") . ($aliases ? $table->getName() . "." : "") . $where->getLeft() . " " . $where->getOperator() . " ";
				if ($where->getType() == 'int') 		$query = $query . $where->getRight();
				elseif ($where->getType() == 'field') 	$query = $query . $where->getRight();
				else 									$query = $query . "'" . $where->getRight() . "'";
				$is_first = false;
			}	
			$wheres = $table->getRawWheres();
			foreach ($wheres as $where) {
				$query = $query . ($is_first ? " WHERE " : " AND ") . $where;
				$is_first = false;
			}
		}
		return $query;
	}
	
	private function groupbyBlock()
	{
		// Build Group by
		$query = '';
		$is_first = true;
		foreach ($this->tables as $table){
			$groupbys = $table->getGroupBy();
			foreach ($groupbys as $group) {
				$query = $query . ($is_first ? " GROUP BY " : ", ") . $table->getName() . "." . $group;
				$is_first = false;
			}			
		}
		foreach ($this->joins as $table){
			$groupbys = $table->getGroupBy();
			foreach ($groupbys as $group) {
				$query = $query . ($is_first ? " GROUP BY " : ", ") . $table->getName() . "." . $group;
				$is_first = false;
			}			
		}
		return $query;
	}
	
	private function orderbyBlock()
	{
		// Build Order by
		$query = '';
		$is_first = true;
		foreach ($this->tables as $table){
			$orderbys = $table->getOrderBy();
			foreach ($orderbys as $order) {
				$query = $query . ($is_first ? " ORDER BY " : ", ") . ($order->isField() ? $table->getName() . "." : "") . $order->getName() . " " . $order->getSort();
				$is_first = false;
			}			
		}
		foreach ($this->joins as $table){
			$orderbys = $table->getOrderBy();
			foreach ($orderbys as $order) {
				$query = $query . ($is_first ? " ORDER BY " : ", ") . ($order->isField() ? $table->getName() . "." : "") . $order->getName() . " " . $order->getSort();
				$is_first = false;
			}			
		}
		return $query;
	}
	
	private function limitBlock()
	{
		// Build limit
		$query = '';
		if ($this->limit->getCount() != -1) {
			$query = $query . " LIMIT ";			
			if ($this->limit->getOffset() != -1) {
				$query = $query . strval($this->limit->getOffset()) . ", ";
			}
			$query = $query . strval($this->limit->getCount());
		}
		return $query;
	}
	
	public function buildUpdate()
	{
		return $this->updateBlock() 
			. " " . $this->setBlock()
			. $this->whereBlock()
			. $this->orderbyBlock()
			. $this->limitBlock();
	}
	
	public function buildSelect() 
	{
		return $this->selectBlock() 
			. " " . $this->fromBlock()
			. $this->whereBlock()
			. $this->groupbyBlock()
			. $this->orderbyBlock()
			. $this->limitBlock();
	}
	
	public function buildDelete() 
	{
		return $this->deleteBlock() 
			. " " . $this->fromBlock(False)
			. $this->whereBlock(False);
	}
	
	public function buildInsert()
	{
		return $this->insertBlock() 
			. " " . $this->valuesBlock();
	}
}
