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

			public static function IsTableProperty($key) {
				return property_exists(get_called_class(), '_' . ucfirst(ltrim($key, '_')));
			}

			public static function GetColumnByProperty($key) {
				if(static::getPropertyDefaultValue($key) == static::IdField) {
					$key .= 'Id';
				}
				return strtolower(trim(preg_replace('#([A-Z])#', '_$1', $key), '_'));
			}

			public static function GetTableName() {
				return strtolower(trim(preg_replace('#([A-Z])#', '_$1', get_called_class()), '_'));
			}

			protected static function getTableColumns() {
				$columns = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if(substr($property, 0, 1) == '_') {
						$columns[$property] = static::getColumnByProperty($property);
					}
				}
				return $columns;
			}

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

			public function Commit(&$stack = array()) {
				return $this->dbCommit($stack);
			}

			protected function dbCommit(&$stack = array()) {
				error_log('dbCommit ' . get_called_class());
				if(in_array($this, $stack)) {
					error_log('In stack, exiting! ' . count($stack));
					return $this;
				}

				$stack[] = $this;

				error_log('Pre ObjectProperties: ' . count($this->ObjectProperties));

				foreach($this->ObjectProperties as $property) {
					$this->$property->Commit($stack);
				}

				error_log('Post ObjectProperties');

				if(count($this->changes) > 0) {
					$values = array();
					$columns = array();
					foreach($this->changes as $property => $b) {
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
					if(is_null($this->Id)) {
						if(DBO::Instance()->Execute(sprintf('INSERT INTO `%s` SET %s;', $this->GetTableName(), implode(', ', $columns)), $values)) {
							error_log('Insert, setting Id #' . DBO::Instance()->LastInsertId() . ' for instance of class ' . get_called_class());
							$this->_Id = DBO::Instance()->LastInsertId();
						}
					} else {
						$values[':id'] = $this->Id;
						DBO::Instance()->Execute(sprintf('UPDATE `%s` SET %s WHERE id = :id;', $this->GetTableName(), implode(', ', $columns)), $values);
					}
				} else {
					error_log('No changes to save!' . print_r($this, true));
				}

				error_log('Pre CollectionProperties: ' . count($this->CollectionProperties));

				foreach($this->CollectionProperties as $property) {
					$this->$property->Commit($stack);
				}

				error_log('Post CollectionProperties');

				return $this;
			}

			public function Delete() {
				return $this->dbDelete();
			}

			protected function dbDelete() {
				if(!is_null($this->Id)) {
					if(DBO::Instance()->Execute(sprintf('DELETE FROM `%s` WHERE id = :id', static::GetTableName()), array(':id' => $this->Id))) {
						return $this;
					}
				}
				throw new Exception('Unable to delete this entry. Check that the object has a valid Id.');
			}

			protected function getObjectProperties() {
				$array = array();
				foreach(get_class_vars(get_called_class()) as $property => $value) {
					if($this->$property instanceof DatabaseObject) {
						$array[] = $property;
					}
				}
				return $array;
			}

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