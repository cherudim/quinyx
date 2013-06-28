<?php

	namespace System {
		class ConfigGroup {
			protected $options = array();

			public function __get($key) {
				if(!isset($this->options[$key])) {
					$this->options[$key] = null;
				}
				return $this->options[$key];
			}

			public function __set($key, $value) {
				$this->options[$key] = $value;
			}
		}
	}

?>