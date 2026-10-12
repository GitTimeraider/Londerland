<?php

trait TwoFAFunctions
{
	// 2FA types a user can turn on (Account Settings); auth_service holds "type::secret" for these, otherwise "internal"
	private const TWO_FA_TYPES = ['google'];

	/**
	 * The 2FA type stored in a user's auth_service, or null when the user has no 2FA.
	 * Anything else (for example "oidc::authentik", written by older versions on OIDC login) is not 2FA.
	 */
	public function twoFAType($authService)
	{
		$parts = explode('::', (string)$authService, 2);
		return (count($parts) === 2 && $parts[1] !== '' && in_array($parts[0], self::TWO_FA_TYPES, true)) ? $parts[0] : null;
	}

	public function create2FA($type)
	{
		$result['type'] = $type;
		switch ($type) {
			case 'google':
				try {
					$google2fa = new PragmaRX\Google2FA\Google2FA();
					$result['secret'] = $google2fa->generateSecretKey();
					$otpauthUrl = $google2fa->getQRCodeUrl(
						$this->config['title'],
						$this->user['username'],
						$result['secret']
					);
					// Render the QR code locally instead of calling an external QR service
					$writer = new BaconQrCode\Writer(
						new BaconQrCode\Renderer\ImageRenderer(
							new BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
							new BaconQrCode\Renderer\Image\SvgImageBackEnd()
						)
					);
					$result['url'] = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($otpauthUrl));
				} catch (\Throwable $e) {
					$this->setResponse(500, $e->getMessage());
					return null;
				}
				break;
			default:
				$this->setAPIResponse('error', $type . ' is not an available to be setup', 404);
				return null;
		}
		$this->setAPIResponse('success', '2FA code created - awaiting verification', 200);
		return $result;
	}

	/* ===== one-time bypass codes for a lost authenticator =====
	 * After a correct password, the 2FA step can ask for a bypass code. It is only written to the container log
	 * (docker logs), so only whoever runs the server can hand it out. It works once, for 15 minutes, and does not
	 * turn 2FA off. Stored hashed in data/config, which the web server never serves.
	 */
	private const TFA_BYPASS_LIFETIME = 900;
	private const TFA_BYPASS_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

	private function tfaBypassFile()
	{
		return dirname($this->userConfigPath) . DIRECTORY_SEPARATOR . 'tfa-bypass.json';
	}

	private function tfaBypassCodes()
	{
		$codes = json_decode((string)@file_get_contents($this->tfaBypassFile()), true) ?: [];
		// Forget expired codes
		return array_filter($codes, function ($entry) {
			return ($entry['expires'] ?? 0) > time();
		});
	}

	private function tfaBypassSave($codes)
	{
		$file = $this->tfaBypassFile();
		file_put_contents($file, json_encode($codes), LOCK_EX);
		@chmod($file, 0600);
	}

	// Bypass codes look like ABCD-EFGH, so they are never mistaken for a 6-digit authenticator code
	public function isTFABypassCode($code)
	{
		return (bool)preg_match('/^[A-Z2-9]{4}-?[A-Z2-9]{4}$/', strtoupper(trim((string)$code)));
	}

	public function createTFABypassCode($user)
	{
		$codes = $this->tfaBypassCodes();
		$id = (string)$user['id'];
		// One new code per minute per account, so the log cannot be flooded
		if (isset($codes[$id]) && $codes[$id]['created'] > time() - 60) {
			$this->setAPIResponse('warning', 'A bypass code was already made in the last minute. Ask your admin for it.', 422);
			return false;
		}
		$code = '';
		for ($i = 0; $i < 8; $i++) {
			$code .= self::TFA_BYPASS_ALPHABET[random_int(0, strlen(self::TFA_BYPASS_ALPHABET) - 1)];
		}
		$code = substr($code, 0, 4) . '-' . substr($code, 4);
		$codes[$id] = ['hash' => password_hash($code, PASSWORD_DEFAULT), 'created' => time(), 'expires' => time() + self::TFA_BYPASS_LIFETIME];
		$this->tfaBypassSave($codes);
		// Only in the container log (docker logs), not in Londerland's own log viewer
		file_put_contents('php://stderr', '[Londerland] 2FA bypass code for user "' . $user['username'] . '": ' . $code . ' (works once, valid for 15 minutes)' . PHP_EOL);
		$this->setLoggerChannel('Authentication', $user['username'])->notice('2FA bypass code requested');
		$this->setAPIResponse('warning', 'A one-time bypass code was written to the server log. Ask your admin for it and enter it as the 2FA code.', 422);
		return true;
	}

	// true (and the code is used up) when $code is the account's current bypass code
	public function useTFABypassCode($user, $code)
	{
		$codes = $this->tfaBypassCodes();
		$id = (string)$user['id'];
		$code = strtoupper(trim((string)$code));
		if (strlen($code) === 8) {
			$code = substr($code, 0, 4) . '-' . substr($code, 4);
		}
		if (!isset($codes[$id]) || !password_verify($code, $codes[$id]['hash'])) {
			return false;
		}
		unset($codes[$id]);
		$this->tfaBypassSave($codes);
		$this->setLoggerChannel('Authentication', $user['username'])->notice('2FA bypassed once with a bypass code');
		return true;
	}

	public function verify2FA($secret, $code, $type)
	{
		if (!$secret || $secret == '') {
			$this->setAPIResponse('error', 'Secret was not supplied or left blank', 422);
			return false;
		}
		if (!$code || $code == '') {
			$this->setAPIResponse('error', 'Code was not supplied or left blank', 422);
			return false;
		}
		if (!$type || $type == '') {
			$this->setAPIResponse('error', 'Type was not supplied or left blank', 422);
			return false;
		}
		switch ($type) {
			case 'google':
				$google2fa = new PragmaRX\Google2FA\Google2FA();
				$google2fa->setWindow(5);
				$valid = $google2fa->verifyKey($secret, $code);
				break;
			default:
				$this->setAPIResponse('error', $type . ' is not an available to be setup', 404);
				return false;
		}
		if ($valid) {
			$this->setAPIResponse('success', 'Verification code verified', 200);
			return true;
		} else {
			$this->setAPIResponse('success', 'Verification code invalid', 401);
			return false;
		}
	}

	public function save2FA($secret, $type)
	{
		if (!$secret || $secret == '') {
			$this->setAPIResponse('error', 'Secret was not supplied or left blank', 422);
			return false;
		}
		if (!$type || $type == '') {
			$this->setAPIResponse('error', 'Type was not supplied or left blank', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['auth_service' => $type . '::' . $secret],
					'WHERE id = ?',
					$this->user['userID']
				)
			),
		];
		$this->setLoggerChannel('Users')->info('User added 2FA');
		$this->setAPIResponse('success', '2FA Added', 200);
		return $this->processQueries($response);
	}

	public function remove2FA()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE users SET',
					['auth_service' => 'internal'],
					'WHERE id = ?',
					$this->user['userID']
				)
			),
		];
		$this->setLoggerChannel('Users')->info('User removed 2FA');
		$this->setAPIResponse('success', '2FA deleted', 204);
		return $this->processQueries($response);
	}
}
