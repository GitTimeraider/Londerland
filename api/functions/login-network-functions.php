<?php

/**
 * Login only from chosen networks (Docker environment variables):
 *  LONDERLAND_LOGIN_ALLOWED_IPS     IPs and subnets that may log in, comma or space separated
 *                                    (for example "192.168.1.0/24, 10.8.0.0/24, 203.0.113.7, fd00::/8").
 *                                    Empty or not set = logging in works from everywhere, as before.
 *  LONDERLAND_TRUSTED_PROXIES       Reverse proxies whose X-Forwarded-For header is believed. Without it the
 *                                    address that connects to the container counts, so behind a proxy every
 *                                    visitor would look like the proxy.
 * Logins are refused in the backend (every token is made by createToken), not only hidden in the browser.
 */
trait LoginNetworkFunctions
{
	private function loginNetworkList($variable)
	{
		$value = getenv($variable);
		if ($value === false) {
			$value = $_SERVER[$variable] ?? '';
		}
		return array_values(array_filter(preg_split('/[\s,;]+/', trim((string)$value))));
	}

	/**
	 * true when $ip is inside $network: a single address or a subnet like 192.168.1.0/24 or fd00::/8
	 */
	public function ipInNetwork($ip, $network)
	{
		$ipBinary = @inet_pton($ip);
		if ($ipBinary === false) {
			return false;
		}
		$parts = explode('/', $network, 2);
		$networkBinary = @inet_pton($parts[0]);
		if ($networkBinary === false) {
			return false;
		}
		// IPv4 written as IPv6 (::ffff:192.168.1.5) is compared as IPv4
		if (strlen($ipBinary) === 16 && strlen($networkBinary) === 4 && strncmp($ipBinary, str_repeat("\0", 10) . "\xff\xff", 12) === 0) {
			$ipBinary = substr($ipBinary, 12);
		}
		if (strlen($ipBinary) !== strlen($networkBinary)) {
			return false;
		}
		$maxBits = strlen($ipBinary) * 8;
		$bits = isset($parts[1]) ? $parts[1] : (string)$maxBits;
		if (!ctype_digit($bits) || (int)$bits > $maxBits) {
			return false;
		}
		$bits = (int)$bits;
		$fullBytes = intdiv($bits, 8);
		if (strncmp($ipBinary, $networkBinary, $fullBytes) !== 0) {
			return false;
		}
		$rest = $bits % 8;
		if ($rest === 0) {
			return true;
		}
		$mask = (0xFF << (8 - $rest)) & 0xFF;
		return (ord($ipBinary[$fullBytes]) & $mask) === (ord($networkBinary[$fullBytes]) & $mask);
	}

	private function ipInNetworks($ip, $networks)
	{
		foreach ($networks as $network) {
			if ($this->ipInNetwork($ip, $network)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Address of the visitor for the login check. X-Forwarded-For is only read when the request comes from a
	 * trusted proxy, and then the right-most address that is not a trusted proxy counts, so a visitor cannot
	 * pick an allowed address by sending the header themselves.
	 */
	public function loginClientIP()
	{
		$remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
		$trusted = $this->loginNetworkList('LONDERLAND_TRUSTED_PROXIES');
		if (!$trusted || !$this->ipInNetworks($remote, $trusted) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $remote;
		}
		$chain = array_map('trim', explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']));
		$client = $remote;
		for ($index = count($chain) - 1; $index >= 0; $index--) {
			if (!filter_var($chain[$index], FILTER_VALIDATE_IP)) {
				break;
			}
			$client = $chain[$index];
			if (!$this->ipInNetworks($client, $trusted)) {
				break;
			}
		}
		return $client;
	}

	public function loginAllowedFromNetwork()
	{
		$allowed = $this->loginNetworkList('LONDERLAND_LOGIN_ALLOWED_IPS');
		if (!$allowed) {
			return true;
		}
		return $this->ipInNetworks($this->loginClientIP(), $allowed);
	}

	/**
	 * Call before anything that logs someone in or creates an account: false (and a 403 API answer) when the
	 * visitor's network may not log in
	 */
	public function loginNetworkCheck()
	{
		if ($this->loginAllowedFromNetwork()) {
			return true;
		}
		$ip = $this->loginClientIP();
		if (isset($this->logger)) {
			$this->setLoggerChannel('Authentication');
			$this->logger->warning('Login refused: ' . $ip . ' is not in LONDERLAND_LOGIN_ALLOWED_IPS');
		}
		$this->setAPIResponse('error', 'Logging in is not allowed from your network (' . $ip . ')', 403);
		return false;
	}
}
