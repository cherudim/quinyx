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

			/**
			 * Magic method for getting the value of an unaccessible or non-existing property. Used to get data through the getVariable-methodes defined in the model, mostly for loading an instance of an object from an Id value.
			 * @throws PropertyException Exception if the property does not exist.
			 */

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

			/**
			 * Magic method when setting a property that does not exist or is unaccessible. Used to set internal variables, optinally through the setVariable-methods defined in the models to be able to validate the inputted data, and store what changes where made.
			 * @throws PropertyException Exception if the property does not exist.
			 */

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