<?php

namespace RedMatrix\RedDAV;

use Sabre\DAV;

/**
 * @brief Authentication backend class for RedDAV.
 *
 * This class also contains some data which is not necessary for authentication
 * like timezone settings.
 *
 * @extends Sabre\DAV\Auth\Backend\AbstractBasic
 *
 * @link http://github.com/friendica/red
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */
class RedBasicAuth extends DAV\Auth\Backend\AbstractBasic {

	/**
	 * @brief This variable holds the currently logged-in channel_address.
	 *
	 * It is used for building path in filestorage/.
	 *
	 * @var string|null
	 */
	protected $channel_name = null;
	/**
	 * channel_id of the current channel of the logged-in account.
	 *
	 * @var int
	 */
	public $channel_id = 0;
	/**
	 * channel_hash of the current channel of the logged-in account.
	 *
	 * @var string
	 */
	public $channel_hash = '';
	/**
	 * Set in mod/cloud.php to observer_hash.
	 *
	 * @var string
	 */
	public $observer = '';
	/**
	 *
	 * @see RedBrowser::set_writeable()
	 * @var \Sabre\DAV\Browser\Plugin
	 */
	public $browser;
	/**
	 * channel_id of the current visited path. Set in RedDirectory::getDir().
	 *
	 * @var int
	 */
	public $owner_id = 0;
	/**
	 * channel_name of the current visited path. Set in RedDirectory::getDir().
	 *
	 * Used for creating the path in cloud/
	 *
	 * @var string
	 */
	public $owner_nick = '';
	/**
	 * Timezone from the visiting channel's channel_timezone.
	 *
	 * Used in @ref RedBrowser
	 *
	 * @var string
	 */
	protected $timezone = '';


	/**
	 * @brief Validates a username and password.
	 *
	 * Guest access is granted with the password "+++".
	 *
	 * @see \Sabre\DAV\Auth\Backend\AbstractBasic::validateUserPass
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function validateUserPass($username, $password) {
		if (trim($password) === '+++') {
			logger('guest: ' . $username);
			return true;
		}

		require_once('include/auth.php');
		$record = account_verify_password($username, $password);
		if ($record && $record['account_default_channel']) {
			$r = q("SELECT * FROM channel WHERE channel_account_id = %d AND channel_id = %d LIMIT 1",
				intval($record['account_id']),
				intval($record['account_default_channel'])
			);
			if ($r) {
				return $this->setAuthenticated($r[0]);
			}
		}
		$r = q("SELECT * FROM channel WHERE channel_address = '%s' LIMIT 1",
			dbesc($username)
		);
		if ($r) {
			$x = q("SELECT account_flags, account_salt, account_password FROM account WHERE account_id = %d LIMIT 1",
				intval($r[0]['channel_account_id'])
			);
			if ($x) {
				// @fixme this foreach should not be needed?
				foreach ($x as $record) {
					if (($record['account_flags'] == ACCOUNT_OK) || ($record['account_flags'] == ACCOUNT_UNVERIFIED)
					&& (hash('whirlpool', $record['account_salt'] . $password) === $record['account_password'])) {
						logger('password verified for ' . $username);
						return $this->setAuthenticated($r[0]);
					}
				}
			}
		}

		$error = 'password failed for ' . $username;
		logger($error);
		log_failed_login($error);

		return false;
	}

	/**
	 * @brief Sets variables and session parameters after successfull authentication.
	 *
	 * @param array $r
	 *  Array with the values for the authenticated channel.
	 * @return bool
	 */
	protected function setAuthenticated($r) {
		$this->channel_name = $r['channel_address'];
		$this->channel_id = $r['channel_id'];
		$this->channel_hash = $this->observer = $r['channel_hash'];
		$_SESSION['uid'] = $r['channel_id'];
		$_SESSION['account_id'] = $r['channel_account_id'];
		$_SESSION['authenticated'] = true;
		return true;
	}

	/**
	 * Sets the channel_name from the currently logged-in channel.
	 *
	 * @param string $name
	 *  The channel's name
	 */
	public function setCurrentUser($name) {
		$this->channel_name = $name;
	}
	/**
	 * Returns information about the currently logged-in channel.
	 *
	 * If nobody is currently logged in, this method should return null.
	 *
	 * @see \Sabre\DAV\Auth\Backend\AbstractBasic::getCurrentUser
	 * @return string|null
	 */
	public function getCurrentUser() {
		return $this->channel_name;
	}

	/**
	 * @brief Sets the timezone from the channel in RedBasicAuth.
	 *
	 * Set in mod/cloud.php if the channel has a timezone set.
	 *
	 * @param string $timezone
	 *  The channel's timezone.
	 * @return void
	 */
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
	}
	/**
	 * @brief Returns the timezone.
	 *
	 * @return string
	 *  Return the channel's timezone.
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @brief Set browser plugin for SabreDAV.
	 *
	 * @see RedBrowser::set_writeable()
	 * @param \Sabre\DAV\Browser\Plugin $browser
	 */
	public function setBrowserPlugin($browser) {
		$this->browser = $browser;
	}

	/**
	 * @brief Prints out all RedBasicAuth variables to logger().
	 *
	 * @return void
	 */
	public function log() {
		logger('channel_name ' . $this->channel_name, LOGGER_DATA);
		logger('channel_id ' . $this->channel_id, LOGGER_DATA);
		logger('channel_hash ' . $this->channel_hash, LOGGER_DATA);
		logger('observer ' . $this->observer, LOGGER_DATA);
		logger('owner_id ' . $this->owner_id, LOGGER_DATA);
		logger('owner_nick ' . $this->owner_nick, LOGGER_DATA);
	}
}