<?php

	namespace Exceptions {
		use Exception;

		class CacheException extends Exception {
			protected $class;
			protected $value;

			public function __construct($class, $value, Exception $prev = null) {
				$this->class = $class;
				$this->value = $value;
			}

			public function getClassName() {
				return (is_object($this->class) ? get_class($this->class) : $this->class);
			}

			public function getValue() {
				return $this->value;
			}

			public function __toString() {
				return '"' . get_called_class() . '" with message "No cache entry for Class ' . $this->getClassName() . ' with Id #' . $this->getValue() . '"';
			}
		}
	}

?>