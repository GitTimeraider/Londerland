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
