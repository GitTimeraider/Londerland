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
}