<?php
/**
 * Copyright 2011 Wim van Ravesteijn
 *
 * This file is part of AEGEE-Europe Statutory Event Applications.
 *
 * AEGEE-Europe Statutory Event Applications is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General
 * Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * AEGEE-Europe Statutory Event Applications is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AEGEE-Europe Statutory Event Applications.  If not, see
 * <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . "/IAuth.php";

class AuthIntranet implements IAuth {
	const USER_ATTR = "uid";

	private $conn;

	private $isBound;

	private $dn;

	public function __construct() {
		$this->isBound = false;
	}

	public function __destruct() {
		if( $this->conn ) {
			@ldap_unbind($this->conn);
			$this->conn = false;
			$this->isBound = false;
		}
	}

	public function getSystemName() {
		return "Intranet";
	}

	public function getRegistrationUrl() {
		return "http://www.intranet.aegee.org/login/signUp";
	}

	public function getAccountEditUrl() {
		return "http://www.intranet.aegee.org/";
	}

	public function auth($user, $password) {
		if( strlen(trim($user))<1 || strlen(trim($password))<1 ) {
			//debug("Empty username or password, cannot connect.");
		}elseif( !($this->conn = @ldap_connect(AUTHINTRANET_URI) ) ) {
			//debug("Failed connecting to ".AUTHINTRANET_URI);
		}else {
			@ldap_set_option($this->conn, LDAP_OPT_TIMELIMIT, 30);
			$dn = self::USER_ATTR . "=" . $user . ",ou=people," . AUTHINTRANET_BASEDN;
			if( @ldap_bind($this->conn, $dn, $password) ) {
				$this->isBound = true;
				$this->dn = $dn;
				//debug("Bound with dn \"".$dn."\"");
				return true;
			}else {
				//debug("Failed bind with dn \"".$dn."\"");
			}
		}
		return false;
	}

	public function getUsername() {
		if( $this->isBound ) {
			if( $res = @ldap_read($this->conn, $this->dn, "(objectClass=*)", array('uid')) ) {
				$entries = @ldap_get_entries($this->conn, $res);
				return $entries[0]['uid'][0];
			}else {
				return null;
			}
		}else {
			return null;
		}
	}

	public function getBoardGroups() {
		return $this->getBoardGroupsInternal($this->getUsername());
	}

	public function getBoardGroupsFor($user) {
		if( !$this->isBound ) {
			if( !($this->conn = @ldap_connect(AUTHINTRANET_URI) ) ) {
				debug("Failed connecting to ".AUTHINTRANET_URI);
			}else {
				@ldap_set_option($this->conn, LDAP_OPT_TIMELIMIT, 30);
				if( @ldap_bind($this->conn, AUTHINTRANET_DN, AUTHINTRANET_PWD) ) {
					$this->isBound = true;
					$this->dn = AUTHINTRANET_DN;
				}else {
					debug("Failed bind with dn \"".AUTHINTRANET_DN."\"");
				}
			}
		}

		return $this->getBoardGroupsInternal($user);
	}

	private function getBoardGroupsInternal($user) {
		$bodies = array();

		if( $this->isBound ) {
			$filter = str_replace('\\', '\\\\', "(memberUid=".self::escapeFilterValue($user).")");
			if( $res = @ldap_search($this->conn, "ou=groups," . AUTHINTRANET_BASEDN, $filter, array('cn')) ) {
				$entries = @ldap_get_entries($this->conn, $res);
				foreach( $entries as $value ) {
					if( preg_match("/^board-([A-Z]{3})$/", $value['cn'][0], $matches) ) {
						$bodies[] = $matches[1];
					}
				}
			}
		}

		return $bodies;
	}

	public function getData($attrs) {
		$data = array();
		if( $this->isBound ) {
			if( in_array("sex", $attrs) || in_array("datebirth", $attrs) ) {
				$attrs[] = "description";
			}
			if( in_array("c", $attrs) ) {
				$attrs[] = "st";
			}
			if( $res = @ldap_read($this->conn, $this->dn, "(objectClass=*)", $attrs) ) {
				$entries = @ldap_get_entries($this->conn, $res);
				foreach($entries[0] as $key => $value) {
					if( !is_numeric($key) && in_array($key, $attrs) && $key!="description" && $key!="st" ) {
						$data[$key] = $value[0];
						if( $key=="0" ) echo $key .": ".$value;
					}
				}
				if( in_array("sex", $attrs) ) {
					switch( $entries[0]['description'][0] ) {
						case "m":
							$data['sex'] = "male";
							break;
						case "f":
							$data['sex'] = "female";
							break;
					}
				}
				if( in_array("datebirth", $attrs) ) {
					$data['datebirth'] = $entries[0]['description'][1];
				}
				if( in_array("c", $attrs) ) {
					$data['c'] = $entries[0]['st'][0];
				}
			}
		}
		return $data;
	}

	public function getEmailByUsername($uid) {
		$mail = null;

		if( !($this->conn = @ldap_connect(AUTHINTRANET_URI) ) ) {
			debug("Failed connecting to ".AUTHINTRANET_URI);
		}else {
			@ldap_set_option($this->conn, LDAP_OPT_TIMELIMIT, 30);
			if( @ldap_bind($this->conn, AUTHINTRANET_DN, AUTHINTRANET_PWD) ) {
				$this->isBound = true;
				$this->dn = AUTHINTRANET_DN;
				if( $res = @ldap_search($this->conn, "ou=people," . AUTHINTRANET_BASEDN, "(".self::USER_ATTR."=".self::escapeFilterValue($uid).")", array('uid', 'mail')) ) {
					$entries = @ldap_get_entries($this->conn, $res);
					if( $entries!=null && $entries[0]!=null && $entries[0]['uid'][0]==$uid ) {
						if( $entries[0]['mail']!=null ) {
							$mail = $entries[0]['mail'][0];
						}
					}
				}
			}else {
				debug("Failed bind with dn \"".AUTHINTRANET_DN."\"");
			}
		}

		return $mail;
	}

	public function getFieldTranslation() {
		$translations = array();
		$translations['BodyCode'] = "ou";
		$translations['FirstName'] = "givenname";
		$translations['LastName'] = "sn";
		$translations['DateBirth'] = "datebirth";
		$translations['Sex'] = "sex";
		$translations['Email'] = "mail";
		$translations['Street'] = "street";
		$translations['Zip'] = "postalcode";
		$translations['City'] = "l";
		$translations['CountryCode'] = "c";
		$translations['Mobile'] = "telephonenumber";
		return $translations;
	}

	/**
	 * The following functions are based on the Net_LDAP2_Util interface class.
	 *
	 * PHP version 5
	 *
	 * @category  Net
	 * @package   Net_LDAP2
	 * @author    Benedikt Hallinger <beni@php.net>
	 * @copyright 2009 Benedikt Hallinger
	 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
	 * @version   SVN: $Id: Util.php 286718 2009-08-03 07:30:49Z beni $
	 * @link      http://pear.php.net/package/Net_LDAP2/
	 */

	/**
	 * Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
	 *
	 * Any control characters with an ACII code < 32 as well as the characters with special meaning in
	 * LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
	 * backslash followed by two hex digits representing the hexadecimal value of the character.
	 *
	 * @param string string to escape
	 * @return string escaped input
	 */
	private function escapeFilterValue($string) {
		// Escaping of filter meta characters
		$string = str_replace('\\', '\5c', $string);
		$string = str_replace('*',  '\2a', $string);
		$string = str_replace('(',  '\28', $string);
		$string = str_replace(')',  '\29', $string);

		// ASCII < 32 escaping
		$string = self::asc2hex32($string);

		if (null === $string) $string = '\0';  // apply escaped "null" if string is empty

		return $string;
	}

	/**
	 * Converts all ASCII chars < 32 to "\HEX"
	 *
	 * @param string $string String to convert
	 * @return string
	 */
	private function asc2hex32($string) {
		for( $i = 0; $i < strlen($string); $i++ ) {
			$char = substr($string, $i, 1);
			if( ord($char) < 32 || ord($char) > 127 ) {
				$hex = dechex(ord($char));
				if( strlen($hex) == 1 ) $hex = '0'.$hex;
				$string = str_replace($char, '\\'.$hex, $string);
			}
		}
		return $string;
	}
}
?>
