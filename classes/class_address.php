<?php

	use System\DatabaseObject;
	use System\DBO;
	use System\ObjectCache;

	class Address extends DatabaseObject {
		protected $_Street;
		protected $_Zip;
		protected $_City;
		protected $_Country;

		/**
		 * Checks the database for a duplicate address, and returns that one if found.
		 * @return Address Instance of Address for the provided data
		 * @throws Exception Exception if no duplicate was found.
		 */

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