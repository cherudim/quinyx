<?php

	namespace System {
		use Exception;
		use Exceptions\DatabaseObjectException;
		use System\Tools;
		use DateTime;

		abstract class DatabaseObject extends Properties {
			const IdField = 'IdField';
			const TimestampField = 'TimestampField';
			const UnixTimeField = 'UnixTimeField';
			const CurrentTime = 'CURRENT_TIMESTAMP';

			protected $_Id; // Require an Id-field for every table

			/**
			 * Checks if the property is a table-column or not. Based on the naming convention that all column variables start with "_".
			 * @return Boolean
			 */

			public static function IsTableProperty($key) {
				return property_exists(get_called_class(), '_' . ucfirst(ltrim($key, '_')));
			}

			/**
			 * Returns the column name for the provided property. Also checks if it is supposed to add _id or not, based on the default value of the property.
			 * @return string The column name.
			 */

			public static function GetColumnByProperty($key) {
				if(static::getPropertyDefaultValue($key) == static::IdField) {
					$key .= 'Id';
				}
				return strtolower(trim(preg_replace('#([A-Z])#', '_$1', $key), '_'));
			}

			/**
			 * Fetches the table name based on the class name.
			 * @return string Table name.
			 */

			public static function GetTableName() {
				return strtolower(trim(preg_replace('#([A-Z])#', '_$1', get_called_class()), '_'));
			}

			/**
			 * Returns an array with the table columns, with the property as the key and the column name as value.
			 * @return array Table columns
			 */

			protected static function getTableColumns() {
				$columns = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if(substr($property, 0, 1) == '_') {
						$columns[$property] = static::getColumnByProperty($property);
					}
				}
				return $columns;
			}

			/**
			 * Creates and loads an instance with values from an array. Looks for column names in the array, assumes the input is from a database query. Also created DateTime objects for columns marked as such.
			 * @return static A new instance loaded with the provided values
			 */

			public static function LoadInstanceFromArray($data) {
				$instance = new static();
				foreach($instance->getTableColumns() as $property => $column) {
					if(isset($data[$column])) {
						if(static::getPropertyDefaultValue($property) == static::TimestampField) {
							try {
								$instance->$property = new DateTime($data[$column]);
							} catch(Exception $e) {
								$instance->$property = $data[$column];
							}
						} else {
							$instance->$property = $data[$column];
						}
					}
				}
				return $instance;
			}

			/**
			 * Recursive method to get the object and it's subobjects as an array, for outputting through the API.
			 * @return array Multi-dimensional array representing the object and it's subobjects.
			 */

			public function AsArray(&$stack = array()) {
				$array = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if(!$this->IsTableProperty($property)) {
						continue;
					}
					$property = trim($property, '_');
					/*if($property == 'Id') {
						$property = 'id';
					}*/
					if($this->$property instanceof DatabaseObject) {
						if(!in_array($this->$property, $stack)) {
							$stack[] = $this->$property;
							$array[$property] = $this->$property->AsArray($stack);
						} else {
							$array[$property] = $this->$property->Id;
						}
					} else if($this->$property instanceof DateTime) {
						$array[$property] = $this->$property->getTimestamp();
					} else if(is_object($this->$property)) {
						$array[$property] = $this->$property;
					} else {
						if($this->$property == $this->getPropertyDefaultValue('_' . $property)) {
							$array[$property] = null;
						} else {
							$array[$property] = Tools::Utf8On($this->$property);
						}
					}
				}
				return $array;
			}

			/**
			 * Magic method for loading an instance of the object based on a column and a value. Matches calls like GetByVariable, taking the part after GetBy as a property, checks if the property exists, and takes the first argument as the value to search for.
			 * @return static An instance of the called class, loaded with data.
			 * @throws DatabaseObjectException Throws this if the entry was not found in the database.
			 * @throws Exception Throws this if the property was not found in the called class.
			 */

			public static function __callStatic($method, $args) {
				$matches = array();
				if(preg_match('#^GetBy([A-Z][a-zA-Z]*)$#', $method, $matches)) {
					if(static::IsTableProperty('_' . $matches[1])) {
						$field = static::GetColumnByProperty('_' . $matches[1]);
						$value = array_shift($args);
						if($row = DBO::Instance()->Execute(sprintf('SELECT t.* FROM `%s` AS t WHERE t.`%s` = :value;', static::GetTableName(), $field), array(':value' => $value))->fetch()) {
							try {
								$instance = ObjectCache::Instance()->Get(get_called_class(), $row['id']);
							} catch(Exception $e) {
								$instance = static::LoadInstanceFromArray($row);
								ObjectCache::Instance()->Set($instance);
							}
							return $instance;
						}
					}
					throw new DatabaseObjectException(get_called_class(), $matches[1], $value);
				}
				throw new Exception(get_called_class() . '::' . $method . ' does not exist or is inaccessible!');
			}

			/**
			 * Method to start saving the instance to the database
			 * @param array An array containing all objects that are being saved in this commit-chain. Used to avoid circular references creating infinite loops. Created autmagically when Commit is called from the code.
			 * @return static The instance, for chaining.
			 */

			public function Commit(&$stack = array()) {
				return $this->dbCommit($stack);
			}

			/**
			 * Method to actually save the instance.
			 * @param array An array containing all objects that are being saved in this commit-chain. Used to avoid circular references creating infinite loops.
			 * @return static The instance, for chaining.
			 */

			protected function dbCommit(&$stack = array()) {
				error_log('dbCommit ' . get_called_class());
				if(in_array($this, $stack)) { // Are we already processing this? Then we should step out here, otherwise infinite loops will be had.
					error_log('In stack, exiting! ' . count($stack));
					return $this;
				}

				$stack[] = $this; // Put this instance in the stack, to prevent infinite loops if it should show up again in the chain.

				error_log('Pre ObjectProperties: ' . count($this->ObjectProperties));

				foreach($this->ObjectProperties as $property) { // Save all subobjects first, if they are new we need their Id before we save the main object.
					$this->$property->Commit($stack);
				}

				error_log('Post ObjectProperties');

				if(count($this->changes) > 0) { // If something is changed, let's save it!
					$values = array();
					$columns = array();
					foreach($this->changes as $property => $b) { // Loop all changes, and enter them in $column and $values to be able to construct a query later on.
						if($this->IsTableProperty($property)) {
							$column = $this->GetColumnByProperty($property);
							if($this->getPropertyDefaultValue($property) == static::TimestampField && $this->$property == static::CurrentTime) {
								$columns[$column] = '`' . $column . '` = CURRENT_TIMESTAMP()';
							} else {
								$columns[$column] = '`' . $column . '` = :' . $column;
								$values[':' . $column] = $this->$property;
							}
						}
					}
					if(is_null($this->Id)) { // Is it a new instance?
						if(DBO::Instance()->Execute(sprintf('INSERT INTO `%s` SET %s;', $this->GetTableName(), implode(', ', $columns)), $values)) {
							error_log('Insert, setting Id #' . DBO::Instance()->LastInsertId() . ' for instance of class ' . get_called_class());
							$this->_Id = DBO::Instance()->LastInsertId();
						}
					} else { // No? Okay then, updating!
						$values[':id'] = $this->Id;
						DBO::Instance()->Execute(sprintf('UPDATE `%s` SET %s WHERE id = :id;', $this->GetTableName(), implode(', ', $columns)), $values);
					}
				} else {
					error_log('No changes to save!' . print_r($this, true));
				}

				error_log('Pre CollectionProperties: ' . count($this->CollectionProperties));

				foreach($this->CollectionProperties as $property) { // Save the collections contained in this object. We do this after the main commit because they might need the main objects's Id.
					$this->$property->Commit($stack);
				}

				error_log('Post CollectionProperties');

				return $this;
			}

			/**
			 * Initiates the deletion of this instance from the database.
			 * @return static The instance.
			 */

			public function Delete() {
				return $this->dbDelete();
			}

			/**
			 * Deletes the instance from the database.
			 * @return static The instance.
			 * @throws Exception Throws if the deletion didn't succeed, for any reason (Most probably that the object to be deleted wasn't ever sent to the database).
			 */

			protected function dbDelete() {
				if(!is_null($this->Id)) {
					if(DBO::Instance()->Execute(sprintf('DELETE FROM `%s` WHERE id = :id', static::GetTableName()), array(':id' => $this->Id))) {
						return $this;
					}
				}
				throw new Exception('Unable to delete this entry. Check that the object has a valid Id.');
			}

			/**
			 * Returns an array containing all properties containing DatabaseObject-instances.
			 * @return array An array with all DatabaseObject-properties
			 */

			protected function getObjectProperties() {
				$array = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if($this->$property instanceof DatabaseObject) {
						$array[] = $property;
					}
				}
				return $array;
			}

			/**
			 * Returns an array containing all properties containing Collection-instances.
			 * @return array An array with all Collection-properties
			 */

			protected function getCollectionProperties() {
				$array = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if($this->$property instanceof Collection) {
						$array[] = $property;
					}
				}
				return $array;
			}
		}
	}

?>