<?php

	namespace Exceptions {
		use Exception;

		class PropertyException extends Exception {
			protected $class;
			protected $property;

			public function __construct($class, $property, Exception $prev = null) {
				$this->class = $class;
				$this->property = $property;
			}

			public function getClassName() {
				return (is_object($this->class) ? get_class($this->class) : $this->class);
			}

			public function getProperty() {
				return $this->property;
			}

			public function __toString() {
				return '"' . get_called_class() . '" with message "Class ' . $this->getClassName() . ' has no property named ' . $this->getProperty() . '"';
			}
		}
	}

?>