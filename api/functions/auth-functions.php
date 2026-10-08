<?php

trait AuthFunctions
{
	public function ldapConnection()
	{
		return new \LdapRecord\Connection($this->ldapConfig());
	}

	// LDAP connection settings for the configured hosts (the first host's port is used for all of them)
	public function ldapConfig()
	{
		$ldapHosts = [];
		$ldapPort = 389;
		foreach (explode(',', $this->config['authBackendHost']) as $i => $value) {
			$digest = parse_url(trim($value));
			$scheme = strtolower(($digest['scheme'] ?? 'ldap'));
			$ldapHosts[] = ($digest['host'] ?? ($digest['path'] ?? ''));
			if ($i == 0) {
				$ldapPort = ($digest['port'] ?? ($scheme == 'ldap' ? 389 : 636));
			}
		}
		return [
			'hosts' => $ldapHosts,
			'base_dn' => $this->config['authBaseDN'],
			'username' => (empty($this->config['ldapBindUsername'])) ? null : $this->config['ldapBindUsername'],
			'password' => (empty($this->config['ldapBindPassword'])) ? null : $this->decrypt($this->config['ldapBindPassword']),
			'port' => (int)$ldapPort,
			'follow_referrals' => false,
			// LdapRecord: use_tls = ldaps:// (Londerland "SSL"), use_starttls = STARTTLS (Londerland "TLS")
			'use_tls' => (bool)$this->config['ldapSSL'],
			'use_starttls' => (bool)$this->config['ldapTLS'],
			'version' => 3,
			'timeout' => 5,
			'options' => [
				// See: http://php.net/ldap_set_option
				LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_ALLOW
			]
		];
	}

	// Username as it is bound to LDAP, including the configured account prefix/suffix
	public function ldapAccountName($username)
	{
		return ((empty($this->config['authBackendHostPrefix'])) ? '' : $this->config['authBackendHostPrefix']) . $username . ((empty($this->config['authBackendHostSuffix'])) ? '' : $this->config['authBackendHostSuffix']);
	}

	public function ldapErrorMessage(\LdapRecord\LdapRecordException $e)
	{
		return $e->getDetailedError() ? $e->getDetailedError()->getErrorMessage() : $e->getMessage();
	}

	public function testConnectionLdap()
	{
		if (!empty($this->config['authBaseDN']) && !empty($this->config['authBackendHost'])) {
			try {
				// Binds with the configured bind user (or anonymously)
				$this->ldapConnection()->connect();
			} catch (\LdapRecord\LdapRecordException $e) {
				// There was an issue binding / connecting to the server.
				$this->setLoggerChannel('LDAP')->error($e);
				$this->setAPIResponse('error', $this->ldapErrorMessage($e), 409);
				return $this->ldapErrorMessage($e);
			}
			$this->setAPIResponse('success', 'LDAP connection successful', 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'authBaseDN and/or BackendHost not supplied', 422);
			return false;
		}
	}

	public function testConnectionLdapLogin($array)
	{
		$username = $array['username'] ?? null;
		$password = $array['password'] ?? null;
		if (empty($username) || empty($password)) {
			$this->setAPIResponse('error', 'Username and/or Password not supplied', 422);
			return false;
		}
		if (!empty($this->config['authBaseDN']) && !empty($this->config['authBackendHost'])) {
			try {
				if ($this->ldapConnection()->auth()->attempt($this->ldapAccountName($username), $password, true)) {
					// Passed.
					$this->setResponse(200, 'LDAP connection successful');
					return true;
				} else {
					// Failed.
					$this->setResponse(401, 'Username/Password Failed to authenticate');
					return false;
				}
			} catch (\LdapRecord\Auth\UsernameRequiredException|\LdapRecord\Auth\PasswordRequiredException $e) {
				// The user didn't supply a username or password.
				$this->setLoggerChannel('LDAP')->error($e);
				$this->setAPIResponse('error', $this->ldapErrorMessage($e), 422);
				return $this->ldapErrorMessage($e);
			} catch (\LdapRecord\LdapRecordException $e) {
				// There was an issue binding / connecting to the server.
				$this->setLoggerChannel('LDAP')->error($e);
				$this->setAPIResponse('error', $this->ldapErrorMessage($e), 500);
				return $this->ldapErrorMessage($e);
			}
		} else {
			$this->setAPIResponse('error', 'authBaseDN and/or BackendHost not supplied', 422);
			return false;
		}
	}

	public function checkPlexToken($token = '')
	{
		try {
			if (($token !== '')) {
				$url = 'https://plex.tv/users/account.json';
				$headers = array(
					'X-Plex-Token' => $token,
					'Content-Type' => 'application/json',
					'Accept' => 'application/json'
				);
				$response = \WpOrg\Requests\Requests::get($url, $headers);
				if ($response->success) {
					return json_decode($response->body, true);
				}
			} else {
				return false;
			}
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('Plex')->error($e);
		}
		return false;
	}

	public function checkPlexUser($username)
	{
		try {
			if (!empty($this->config['plexToken'])) {
				$url = 'https://plex.tv/api/users';
				$headers = array(
					'X-Plex-Token' => $this->config['plexToken'],
				);
				$response = \WpOrg\Requests\Requests::get($url, $headers);
				if ($response->success) {
					libxml_use_internal_errors(true);
					$userXML = simplexml_load_string($response->body);
					if (is_array($userXML) || is_object($userXML)) {
						$usernameLower = strtolower($username);
						foreach ($userXML as $child) {
							if (isset($child['username']) && strtolower($child['username']) == $usernameLower || isset($child['email']) && strtolower($child['email']) == $usernameLower) {
								$this->setLoggerChannel('Plex')->info('Found User on Friends List');
								$machineMatches = false;
								if ($this->config['plexStrictFriends']) {
									foreach ($child->Server as $server) {
										if ((string)$server['machineIdentifier'] == $this->config['plexID']) {
											$machineMatches = true;
										}
									}
								} else {
									$machineMatches = true;
								}
								if ($machineMatches) {
									$this->setLoggerChannel('Plex')->info('User Approved for Login');
									return true;
								} else {
									$this->setLoggerChannel('Plex')->warning('User not Approved User');
								}
							}
						}
					}
				}
			}
			return false;
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('Plex')->error($e);
		}
		return false;
	}

	public function plugin_auth_plex($username, $password)
	{
		try {
			$usernameLower = strtolower($username);
			//Login User
			$url = 'https://plex.tv/users/sign_in.json';
			$headers = array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
				'X-Plex-Product' => 'Londerland',
				'X-Plex-Version' => '2.0',
				'X-Plex-Client-Identifier' => $this->config['uuid'],
			);
			$data = array(
				'user[login]' => $username,
				'user[password]' => $password,
			);
			$options = array('timeout' => 30);
			$response = \WpOrg\Requests\Requests::post($url, $headers, $data, $options);
			if ($response->success) {
				$json = json_decode($response->body, true);
				if ((is_array($json) && isset($json['user']) && isset($json['user']['username'])) && strtolower($json['user']['username']) == $usernameLower || strtolower($json['user']['email']) == $usernameLower) {
					if ((!empty($this->config['plexAdmin']) && (strtolower($this->config['plexAdmin']) == strtolower($json['user']['username']) || strtolower($this->config['plexAdmin']) == strtolower($json['user']['email']))) || $this->checkPlexUser($json['user']['username'])) {
						return array(
							'username' => $json['user']['username'],
							'email' => $json['user']['email'],
							'image' => $json['user']['thumb'],
							'token' => $json['user']['authToken']
						);
					}
				}
			}
			return false;
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('Plex')->error($e);
		}
		return false;
	}

	// Pass credentials to LDAP backend
	public function plugin_auth_ldap($username, $password)
	{
		if (!empty($this->config['authBaseDN']) && !empty($this->config['authBackendHost'])) {
			try {
				$connection = $this->ldapConnection();
				$accountDN = $this->ldapAccountName($username);
				if ($connection->auth()->attempt($accountDN, $password)) {
					// Passed - try and get email from LDAP server
					$record = $connection->query()->find($accountDN);
					return array(
						'email' => $record['mail'][0] ?? null
					);
				} else {
					// Failed.
					return false;
				}
			} catch (\LdapRecord\LdapRecordException $e) {
				// There was an issue binding / connecting to the server, or no username/password was supplied.
				$this->setLoggerChannel('LDAP')->error($e);
			}
		}
		return false;
	}

	// Ldap Auth Missing Dependency
	public function plugin_auth_ldap_disabled()
	{
		return 'LDAP - Disabled (Dependency: php-ldap missing!)';
	}

	// Pass credentials to FTP backend
	public function plugin_auth_ftp($username, $password)
	{
		// Calculate parts
		$digest = parse_url($this->config['authBackendHost']);
		$scheme = strtolower((isset($digest['scheme']) ? $digest['scheme'] : (function_exists('ftp_ssl_connect') ? 'ftps' : 'ftp')));
		$host = (isset($digest['host']) ? $digest['host'] : (isset($digest['path']) ? $digest['path'] : ''));
		$port = (isset($digest['port']) ? $digest['port'] : 21);
		// Determine Connection Type
		if ($scheme == 'ftps') {
			$conn_id = ftp_ssl_connect($host, $port, 20);
		} elseif ($scheme == 'ftp') {
			$conn_id = ftp_connect($host, $port, 20);
		} else {
			return false;
		}
		// Check if valid FTP connection
		if ($conn_id) {
			// Attempt login
			@$login_result = ftp_login($conn_id, $username, $password);
			ftp_close($conn_id);
			// Return Result
			if ($login_result) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Pass credentials to Emby Backend
	public function plugin_auth_emby_local($username, $password)
	{
		try {
			$url = $this->qualifyURL($this->config['embyURL']) . '/Users/AuthenticateByName';
			$headers = array(
				'Authorization' => 'Emby UserId="e8837bc1-ad67-520e-8cd2-f629e3155721", Client="None", Device="Londerland", DeviceId="xxx", Version="1.0.0.0"',
				'Content-Type' => 'application/json',
			);
			$data = array(
				'Username' => $username,
				'pw' => $password,
				'Password' => sha1($password),
				'PasswordMd5' => md5($password),
			);
			$response = \WpOrg\Requests\Requests::post($url, $headers, json_encode($data));
			if ($response->success) {
				$json = json_decode($response->body, true);
				if (is_array($json) && isset($json['SessionInfo']) && isset($json['User']) && $json['User']['HasPassword'] == true) {
					// Login Success - Now Logout Emby Session As We No Longer Need It
					$headers = array(
						'X-Emby-Token' => $json['AccessToken'],
						'X-Mediabrowser-Token' => $json['AccessToken'],
					);
					$response = \WpOrg\Requests\Requests::post($this->qualifyURL($this->config['embyURL']) . '/Sessions/Logout', $headers, array());
					if ($response->success) {
						return true;
					}
				}
			}
			return false;
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('Emby')->error($e);
		}
		return false;
	}

	// Pass credentials to JellyFin Backend
	public function plugin_auth_jellyfin($username, $password)
	{
		try {
			$url = $this->qualifyURL($this->config['jellyfinURL']) . '/Users/authenticatebyname';
			$headers = array(
				'X-Emby-Authorization' => 'MediaBrowser Client="Londerland Auth", Device="Londerland", DeviceId="orgv2", Version="2.0"',
				'Content-Type' => 'application/json',
			);
			$data = array(
				'Username' => $username,
				'Pw' => $password
			);
			$response = \WpOrg\Requests\Requests::post($url, $headers, json_encode($data));
			if ($response->success) {
				$json = json_decode($response->body, true);
				if (is_array($json) && isset($json['SessionInfo']) && isset($json['User']) && $json['User']['HasPassword'] == true) {
					$this->setLoggerChannel('JellyFin')->info('Found User and Logged In');
					// Login Success - Now Logout JellyFin Session As We No Longer Need It
					$headers = array(
						'X-Emby-Authorization' => 'MediaBrowser Client="Londerland Auth", Device="Londerland", DeviceId="orgv2", Version="2.0", Token="' . $json['AccessToken'] . '"',
						'Content-Type' => 'application/json',
					);
					$response = \WpOrg\Requests\Requests::post($this->qualifyURL($this->config['jellyfinURL']) . '/Sessions/Logout', $headers, array());
					if ($response->success) {
						return true;
					}
				}
			}
			return false;
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('JellyFin')->error($e);
		}
		return false;
	}

	// Authenticate against emby connect
	public function plugin_auth_emby_connect($username, $password)
	{
		// Emby disabled EmbyConnect on their API
		// https://github.com/MediaBrowser/Emby/issues/3553
		//return plugin_auth_emby_local($username, $password);
		try {
			$this->setLoggerChannel('Emby')->info('Attempting to Login with Emby Connect for user: ' . $username);
			$connectURL = 'https://connect.emby.media/service/user/authenticate';
			$headers = array(
				'Accept' => 'application/json',
				'X-Application' => 'Londerland/2.0'
			);
			$data = array(
				'nameOrEmail' => $username,
				'rawpw' => $password,
			);
			$response = \WpOrg\Requests\Requests::post($connectURL, $headers, $data);
			if ($response->success) {
				$json = json_decode($response->body, true);
				if (is_array($json) && isset($json['AccessToken']) && isset($json['User'])) {
					$connectUser = $json['User'];
				} else {
					$this->setLoggerChannel('Emby')->warning('Bad Response');
					return false;
				}
			} else {
				$this->setLoggerChannel('Emby')->warning('401 From Emby Connect');
				return false;
			}
			// Get A User
			if ($connectUser) {
				$url = $this->qualifyURL($this->config['embyURL']) . '/Users?api_key=' . $this->config['embyToken'];
				$response = \WpOrg\Requests\Requests::get($url);
				if ($response->success) {
					$json = json_decode($response->body, true);
					if (is_array($json)) {
						foreach ($json as $key => $value) { // Scan for this user
							if (isset($value['ConnectUserName']) && isset($value['ConnectLinkType'])) { // Qualify as connect account
								if (strtolower($value['ConnectUserName']) == strtolower($connectUser['Name']) || strtolower($value['ConnectUserName']) == strtolower($connectUser['Email'])) {
									$this->setLoggerChannel('Emby')->info('Found User');
									return array(
										'email' => $connectUser['Email'],
										'username' => $connectUser['Name']
										//'image' => $json['User']['ImageUrl'],
									);
								}
							}
						}
					}
				}
			}
			return false;
		} catch (\WpOrg\Requests\Exception $e) {
			$this->setLoggerChannel('Emby')->error($e);
			return false;
		}
	}

	// Authenticate Against Emby Local (first) and Emby Connect
	public function plugin_auth_emby_all($username, $password)
	{
		// Emby disabled EmbyConnect on their API
		// https://github.com/MediaBrowser/Emby/issues/3553
		$localResult = $this->plugin_auth_emby_local($username, $password);
		//return $localResult;
		if ($localResult) {
			return $localResult;
		} else {
			return $this->plugin_auth_emby_connect($username, $password);
		}
	}

}