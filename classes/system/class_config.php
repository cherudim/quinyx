<?php

	namespace System {
		use stdClass;
		use Exception;

		class Config {
			protected $config;

			protected static $instance;

			public function __construct($file = 'config.ini') {
				$this->config = new stdClass();

				$this->config->core = new ConfigGroup();
				$this->config->core->root = dirname(dirname(dirname(__FILE__)));

				$ini = $this->config->core->root . DIRECTORY_SEPARATOR . $file;
				if(file_exists($ini)) {
					$config = parse_ini_file($ini, true);
				} else {
					throw new Exception('No config.ini found in ' . $this->config->core->root);
				}

				if(isset($config)) {
					foreach($config as $key => $value) {
						if(is_array($value)) {
							$key = strtolower($key);
							$tmp = $value;
							$value = new ConfigGroup();
							foreach($tmp as $k => $v) {
								$k = strtolower($k);
								$value->$k = $v;
							}
						}
						$this->config->$key = $value;
					}
				}
			}

			public static function Instance() {
				if(!(static::$instance instanceof static)) {
					static::$instance = new static();
				}
				return static::$instance;
			}

			public function __get($value) {
				$value = strtolower($value);
				if(isset($this->config->$value) && $this->config->$value instanceof ConfigGroup) {
					return $this->config->$value;
				} else {
					return new ConfigGroup;
				}
			}
		}
	}

?>