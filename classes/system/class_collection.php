<?php

	namespace System {
		use Iterator;
		use Countable;
		use ArrayAccess;
		use Exception;
		use Exceptions\DatabaseObjectException;

		class Collection implements Iterator, Countable, ArrayAccess {
			protected $array = array();
			protected $added = array();
			protected $removed = array();

			/**
			 * Fetches the class name for the model contained in this collection.
			 * @return string The class name.
			 * @throws Exception Thrown if the Collection name can't be resolved to an existing class.
			 */

			protected static function getContainedClass() {
				$collection = get_called_class();
				$class = str_replace('Collection', '', get_called_class());
				if(!$class || !class_exists($class)) {
					throw new Exception(get_called_class() . ' does not seem to have a class to collect. That\'s horrible!');
				}
				return $class;
			}

			/**
			 * Magic method for loading a collection of the object based on a column and a value. Matches calls like GetByVariable, taking the part after GetBy as a property, checks if the property exists, and takes the first argument as the value to search for.
			 * @return static A collection containing model-instances of all matches, loaded with data.
			 * @throws DatabaseObjectException Throws this if the entry was not found in the database.
			 * @throws Exception Throws this if the property was not found in the called class.
			 */

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

			/**
			 * Loads a collection with all instances in the database.
			 * @return static A collection containing model-instances of all entries, loaded with data.
			 */

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

			/**
			 * Recursive method to get the collection's objects and thier subobjects as an array, for outputting through the API.
			 * @return array Multi-dimensional array representing the collection and it's object.
			 */

			public function AsArray() {
				$array = array();
				foreach($this as $item) {
					$array[] = $item->AsArray();
				}
				return $array;
			}

			/**
			 * Adds an instance to the Collection.
			 * @throws Exception Thrown if the instance already exists in the collection
			 */

			public function Add(DatabaseObject $object, $change = true) {
				if($this->Exists($object) !== false) {
					throw new \Exception("This Collection already contains that object!");
				}
				$this->array[] = $object;
				if($change) {
					$this->added[] = $object;
				}
			}

			/**
			 * Removes an instance from the Collection.
			 * @throws Exception Thrown if the instance was not found in the Collection.
			 */
					
			public function Remove(DatabaseObject $object) {
				$key = $this->Exists($object);
				if($key !== false) {
					$this->removed[] = $this->array[$key];
					unset($this->array[$key]);
					return $this;
				}
				throw new \Exception("Objektet hittades inte!");
			}

			/**
			 * Empties the collection
			 */

			public function Clear() {
				foreach($this->array as $key => $value) {
					$this->removed = $this->array[$key];
					unset($this->array[$key]);
				}
				return $this;
			}

			/**
			 * Checks if the instance exists in the Collection.
			 * @return mixed The index of the instance if found, otherwise bool false.
			 */
			
			public function Exists(DatabaseObject $object) {
				foreach($this->array as $index => $item) {
					if($item === $object || (get_class($item) == get_class($object) && !is_null($item->Id) && $item->Id == $object->Id)) {
						return $index;
					}
				}
				return false;
			}

			/**
			 * Method to start saving the collection to the database
			 * @param array An array containing all objects that are being saved in this commit-chain. Used to avoid circular references creating infinite loops. Created autmagically when Commit is called from the code.
			 * @return static The collection, for chaining.
			 */

			public function Commit(&$stack = array()) {
				return $this->dbCommit($stack);
			}

			/**
			 * Method to actually save the collection.
			 * @param array An array containing all objects that are being saved in this commit-chain. Used to avoid circular references creating infinite loops.
			 * @return static The collection, for chaining.
			 */

			protected function dbCommit(&$stack = array()) {
				foreach($this as $item) {
					$item->Commit($stack);
				}
				return $this;
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