<?php

	namespace System {
		class ConfigGroup {
			protected $options = array();

			/**
			 * Magic method to get the requested value from this config group.
			 * @return mixed String or int if found, else null
			 */

			public function __get($key) {
				if(!isset($this->options[$key])) {
					$this->options[$key] = null;
				}
				return $this->options[$key];
			}

			/**
			 * Used to temporarily set a value in the config. It is not saved to the config file.
			 */

			public function __set($key, $value) {
				$this->options[$key] = $value;
			}
		}
	}

?>