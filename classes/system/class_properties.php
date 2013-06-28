<?php

	namespace System {
		use Exceptions\PropertyException;

		class Properties {
			protected $changes = array();

			protected function change($key, $value) {
				$this->changes[$key] = 1;
			}

			protected static function hasProperty($key) {
				if(property_exists(get_called_class(), $key)) {
					return $key;
				} else if(property_exists(get_called_class(), '_' . ucfirst($key))) {
					return '_' . ucfirst($key);
				} else if(property_exists(get_called_class(), lcfirst($key))) {
					return lcfirst($key);
				}
				return false;
			}

			protected static function getPropertyDefaultValue($key) {
				$vars = get_class_vars(get_called_class());
				if(in_array($key, array_keys($vars))) {
					return $vars[$key];
				}
				throw new PropertyException(get_called_class(), $key);
			}

			protected static function hasMethod($key) {
				return method_exists(get_called_class(), $key);
			}

			public function __get($var) {
				$var = ucfirst($var);
				if($this->hasMethod('get' . $var)) {
					$method = 'get' . $var;
					return $this->$method();
				} else if($this->hasProperty($var)) {
					$var = $this->hasProperty($var);
					return $this->$var;
				}
				throw new PropertyException(get_called_class(), $var);
			}

			public function __set($var, $value) {
				$var = ucfirst($var);
				if($this->hasMethod('set' . $var)) {
					$method = 'set' . $var;
					$var = $this->hasProperty($var);
					$this->$method($value);
					$this->change($var, $value);
				} else if($this->hasProperty($var)) {
					$var = $this->hasProperty($var);
					$this->$var = $value;
					$this->change($var, $value);
				} else {
					throw new PropertyException($this, $var);
				}
			}
		}
	}

?>