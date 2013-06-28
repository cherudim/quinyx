<?php

	namespace System {
		use Exceptions\CacheException;
		use Exception;

		class ObjectCache {
			protected $cache = array();

			protected static $instance;

			public static function Instance() {
				if(!(static::$instance instanceof static)) {
					static::$instance = new static();
				}
				return static::$instance;
			}

			public function Get($class, $id) {
				$class_name = (is_object($class) ? get_class($class) : $class);
				if(isset($this->cache[$class_name][$id]) && $this->cache[$class_name][$id] instanceof DatabaseObject) {
					return $this->cache[$class_name][$id];
				}
				throw new CacheException($class, $id);
			}

			public function Set(DatabaseObject $instance) {
				if($instance instanceof DatabaseObject && !is_null($instance->Id)) {
					$this->cache[get_class($instance)][$instance->id] = $instance;
					return;
				}
				throw new Exception('Unable to add object to Cache, is the Id value valid?');
			}
		}
	}

?>