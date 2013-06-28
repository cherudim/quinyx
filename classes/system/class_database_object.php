<?php

	namespace System {
		use Exception;
		use Exceptions\DatabaseObjectException;

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
					error_log($property . ' = ' . $column);
					if(isset($data[$column])) {
						$instance->$property = $data[$column];
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
					if($this->$property instanceof DatabaseObject) {
						if(!in_array($this->$property, $stack)) {
							$stack[] = $this->$property;
							$array[$property] = $this->$property->AsArray($stack);
						} else {
							$array[$property] = $this->$property->Id;
						}
					} else {
						$array[$property] = utf8_encode($this->$property);
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
		}
	}

?>