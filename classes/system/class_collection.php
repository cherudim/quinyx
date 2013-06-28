<?php

	namespace System {
		use Iterator;
		use Countable;
		use ArrayAccess;
		use Exception;
		use Exceptions\DatabaseObjectException;

		class Collection implements Iterator, Countable, ArrayAccess {
			protected $array;
			protected $added;
			protected $removed;

			protected static function getContainedClass() {
				$collection = get_called_class();
				$class = str_replace('Collection', '', get_called_class());
				if(!$class || !class_exists($class)) {
					throw new Exception(get_called_class() . ' does not seem to have a class to collect. That\'s horrible!');
				}
				return $class;
			}

			public static function __callStatic($method, $args) {
				$matches = array();
				if(preg_match('#^GetBy([A-Z][a-zA-Z]*)$#', $method, $matches)) {
					$class = static::getContainedClass();
					if($class::IsTableProperty('_' . $matches[1])) {
						$field = $class::GetColumnByProperty('_' . $matches[1]);
						$value = array_shift($args);
						$collection = new static();
						foreach($row = DBO::Instance()->Execute(sprintf('SELECT t.* FROM `%s` AS t WHERE t.`%s` = :value;', $class::GetTableName(), $field), array(':value' => $value)) as $row) {
							try {
								$instance = ObjectCache::Instance()->Get(get_called_class(), $row['id']);
							} catch(Exception $e) {
								$instance = $class::LoadInstanceFromArray($row);
								ObjectCache::Instance()->Set($instance);
							}
							$collection->Add($instance, false);
						}
						return $collection;
					}
					throw new DatabaseObjectException(get_called_class(), $matches[1], $value);
				}
				throw new Exception(get_called_class() . '::' . $method . ' does not exist or is inaccessible!');
			}

			public static function GetAll() {
				$class = static::getContainedClass();
				$collection = new static();
				foreach($row = DBO::Instance()->Execute(sprintf('SELECT t.* FROM `%s` AS t;', $class::GetTableName())) as $row) {
					try {
						$instance = ObjectCache::Instance()->Get(get_called_class(), $row['id']);
					} catch(Exception $e) {
						$instance = $class::LoadInstanceFromArray($row);
						ObjectCache::Instance()->Set($instance);
					}
					$collection->Add($instance, false);
				}
				return $collection;
			}

			public function AsArray() {
				$array = array();
				foreach($this as $item) {
					$array[] = $item->AsArray();
				}
				return $array;
			}

			public function Add(DatabaseObject $object, $change = true) {
				if($this->Exists($object) !== false) {
					throw new \Exception("This Collection already contains that object!");
				}
				$this->array[] = $object;
				if($change) {
					$this->added[] = $object;
				}
			}
					
			public function Remove(DatabaseObject $object) {
				$key = $this->Exists($object);
				if($key !== false) {
					$this->removed[] = $this->array[$key];
					unset($this->array[$key]);
					return $this;
				}
				throw new \Exception("Objektet hittades inte!");
			}

			public function Clear() {
				foreach($this->array as $key => $value) {
					$this->removed = $this->array[$key];
					unset($this->array[$key]);
				}
				return $this;
			}
			
			public function Exists(DatabaseObject $object) {
				foreach($this->array as $index => $item) {
					if($item === $object || (get_class($item) == get_class($object) && !is_null($item->Id) && $item->Id == $object->Id)) {
						return $index;
					}
				}
				return false;
			}

			// ArrayAccess
			
			public function offsetSet($offset, $value) {
				if(!is_null($offset)) {
					$this->array[$offset] = $value;
				}
				else {
					$this->array[] = $value;
				}
			}
			
			public function offsetGet($offset) {
				return isset($this->array[$offset]) ? $this->array[$offset] : null;
			}
			
			public function offsetExists($offset) {
				return isset($this->array[$offset]);
			}
			
			public function offsetUnset($offset) {
				unset($this->array[$offset]);
			}
			
			// Iterable
			
			public function rewind() {
				reset($this->array);
			}
			
			public function current() {
				return current($this->array);
			}
			
			public function key() {
				return key($this->array);
			}
			
			public function next() {
				return next($this->array);
			}
			
			public function valid() {
				return current($this->array) !== false;
			}
			
			// Countable
			
			public function count() {
				return count($this->array);
			}
		}
	}

?>