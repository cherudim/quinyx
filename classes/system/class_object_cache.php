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

			/**
			 * Fetches an instance from the cache if it exists, otherwise throws an exception
			 * @param mixed $class The class to be fetched.
			 * @param int $id The id to be fetched.
			 * @return static The cached instance of the class.
			 * @throws CacheException Thrown if the instance does not exist.
			 */

			public function Get($class, $id) {
				$class_name = (is_object($class) ? get_class($class) : $class);
				if(isset($this->cache[$class_name][$id]) && $this->cache[$class_name][$id] instanceof DatabaseObject) {
					return $this->cache[$class_name][$id];
				}
				throw new CacheException($class, $id);
			}

			/**
			 * Pushes the provieded DatabaseObject to the cache, if possible.
			 * @param DatabaseObject $instance The instance to be pushed.
			 * @return void.
			 * @throws Exception Thrown if the instance could not be pushed.
			 */

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