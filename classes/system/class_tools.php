<?php

	namespace System {
		class Tools {
			public static function Utf8On($string) {
				if(mb_check_encoding($string, 'UTF-8')) {
					return $string;
				}
				return utf8_encode($string);
			}

			public static function Utf8Off($string) {
				if(!mb_check_encoding($string, 'UTF-8')) {
					return $string;
				}
				return utf8_decode($string);
			}
		}
	}

?>