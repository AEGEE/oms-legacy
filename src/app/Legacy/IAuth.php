<?php
// https://www.zeus.aegee.org/svn/flixbus/trunk/include/auth/IAuth.php
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

namespace App\Legacy;

interface IAuth {
	/**
	 * Get the name of the authentication system (to be shown at the login prompt)
	 *
	 * @return string name of the authentication system
	 */
	public function getSystemName();

	/**
	 * Get the URL for account registration
	 *
	 * @return string url of the authentication system where new accounts can be registered
	 */
	public function getRegistrationUrl();

	/**
	 * Get the URL for editing an account
	 *
	 * @return string url of the authentication system where accounts can be edited
	 */
	public function getAccountEditUrl();

	/**
	 * Authenticate a user
	 *
	 * @param $user Username to authenticate with
	 * @param $password Password to authenticate with
	 * @return boolean true in case of success, otherwise false
	 */
	public function auth($user, $password);

	/**
	 * Get the username of the logged in user (via auth())
	 *
	 * @return string username of the user authenticated via auth()
	 */
	public function getUsername();

	/**
	 * Get an array of BodyCodes of the bodies where the authenticated user is board member
	 *
	 * @return array BodyCodes of bodies where the authenticated user is board member
	 */
	public function getBoardGroups();

	/**
	 * Get an array of BodyCodes of the bodies where the given user is board member
	 *
	 * @return array BodyCodes of bodies where the authenticated user is board member
	 */
	public function getBoardGroupsFor($user);

	/**
	 * Retrieve a list of attributes of the authenticated user
	 *
	 * @param $attrs array of attributes to retrieve
	 * @return array of the requested attributes (where available)
	 */
	public function getData($attrs);

	/**
	 * Get e-mail by username
	 *
	 * @param $uid username
	 * @return string e-mail address of the given username, or null when not found
	 */
	public function getEmailByUsername($uid);

	/**
	 * Return an array with field translations. Expected keys (do not have to be present):
	 * - BodyCode
	 * - FirstName
	 * - LastName
	 * - DateBirth
	 * - Sex
	 * - Email
	 * - Street
	 * - Zip
	 * - City
	 * - CountryCode
	 * - Phone
	 * - Mobile
	 * - Fax
	 *
	 * @return array with above keys and the related field as value (keys from getData(..))
	 */
	public function getFieldTranslation();
}
?>
