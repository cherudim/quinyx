<?php

	use System\DatabaseObject;
	use System\DBO;

	class Address extends DatabaseObject {
		protected $_Street;
		protected $_Zip;
		protected $_City;
		protected $_Country;

		public static function GetDuplicate($Street, $Zip, $City, $Country) {
			if($row = DBO::Instance()->Execute('SELECT a.* FROM address AS a WHERE a.street = :street AND a.zip = :zip AND a.city = :city AND a.country = :country', array(':street' => $Street, ':zip' => $Zip, ':city' => $City, ':country' => $Country))->fetch()) {
				try {
					$instance = ObjectCache::Instance()->Get(get_called_class(), $row['id']);
				} catch(Exception $e) {
					$instance = static::LoadInstanceFromArray($row);
					ObjectCache::Instance()->Set($instance);
				}
				return $instance;
			}
			throw new Exception;
		}
	}

?>