<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Thomas Hucke <thucke@web.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General protected License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General protected License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General protected License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Service for setting cookies like Typo3 does
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU protected License, version 2
 */
class Tx_ThRating_Service_CookieService implements t3lib_Singleton {

	/**
	 * Gets the domain to be used on setting cookies.
	 * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'].
	 * Protected function taken from t3lib_userAuth (t3 4.7.7)
	 *
	 * @return	string		The domain to be used on setting cookies
	 */
	protected function getCookieDomain() {
		$result = '';
		$cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'];
			// If a specific cookie domain is defined for a given TYPO3_MODE,
			// use that domain
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cookieDomain'])) {
			$cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieDomain'];
		}

		if ($cookieDomain) {
			if ($cookieDomain{0} == '/') {
				$match = array();
				$matchCnt = @preg_match($cookieDomain, t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'), $match);
				if ($matchCnt === FALSE) {
					t3lib_div::sysLog('The regular expression for the cookie domain (' . $cookieDomain . ') contains errors. The session is not shared across sub-domains.', 'Core', 3);
				} elseif ($matchCnt) {
					$result = $match[0];
				}
			} else {
				$result = $cookieDomain;
			}
		}
		return $result;
	}	

	/**
	 * Sets the cookie
	 * Protected function taken from t3lib_userAuth (t3 4.7.7)
	 *
	 * @param	string 	$cookieName		identifier for the cookie
	 * @param	string 	$cookieValue	cookie value
	 * @param	int 	$cookieExpire	expire time for the cookie
	 *
	 * @return	void
	 */
	static function setVoteCookie($cookieName, $cookieValue, $cookieExpire=0 ) {
		// do not set session cookies
		If ( !empty($cookieExpire) ) {
			$settings = $GLOBALS['TYPO3_CONF_VARS']['SYS'];

			// Get the domain to be used for the cookie (if any):
			$cookieDomain = Tx_ThRating_Service_CookieService::getCookieDomain();
			// If no cookie domain is set, use the base path:
			$cookiePath = ($cookieDomain ? '/' : t3lib_div::getIndpEnv('TYPO3_SITE_PATH'));
			// Use the secure option when the current request is served by a secure connection:
			$cookieSecure = (bool) $settings['cookieSecure'] && t3lib_div::getIndpEnv('TYPO3_SSL');
			// Deliver cookies only via HTTP and prevent possible XSS by JavaScript:
			$cookieHttpOnly = (bool) $settings['cookieHttpOnly'];

			// Do not set cookie if cookieSecure is set to "1" (force HTTPS) and no secure channel is used:
			if ((int) $settings['cookieSecure'] !== 1 || t3lib_div::getIndpEnv('TYPO3_SSL')) {
				setcookie(
					$cookieName,
					$cookieValue,
					$cookieExpire,
					$cookiePath,
					$cookieDomain,
					$cookieSecure,
					$cookieHttpOnly
				);
			} else {
				throw new t3lib_exception(
					'Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].',
					1254325546
				);
			}
		}
	}
}
?>