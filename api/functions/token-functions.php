<?php

trait TokenFunctions
{
	public function configToken()
	{
		return Lcobucci\JWT\Configuration::forSymmetricSigner(
		// You may use any HMAC variations (256, 384, and 512)
			new Lcobucci\JWT\Signer\Hmac\Sha256(),
			Lcobucci\JWT\Signer\Key\InMemory::plainText($this->jwtSigningKey())
		// You may also override the JOSE encoder/decoder if needed by providing extra arguments here
		);
	}

	// HMAC-SHA256 requires a key of at least 256 bits; stretch shorter hash keys so they remain usable
	public function jwtSigningKey()
	{
		$hashKey = (string)$this->config['organizrHash'];
		return (strlen($hashKey) >= 32) ? $hashKey : hash('sha256', $hashKey);
	}

	public function validationConstraints()
	{
		return [
			new Lcobucci\JWT\Validation\Constraint\IssuedBy('Organizr'),
			new Lcobucci\JWT\Validation\Constraint\PermittedFor('Organizr'),
			new Lcobucci\JWT\Validation\Constraint\LooseValidAt(Lcobucci\Clock\SystemClock::fromUTC())
		];
	}

	public function jwtParse($userToken)
	{
		try {
			$result = [];
			// Check Token with JWT
			// Set key
			if (!isset($this->config['organizrHash'])) {
				return null;
			}
			$config = $this->configToken();
			assert($config instanceof Lcobucci\JWT\Configuration);
			$token = $config->parser()->parse($userToken);
			assert($token instanceof Lcobucci\JWT\UnencryptedToken);
			$constraints = $this->validationConstraints();
			if (!$config->validator()->validate($token, ...$constraints)) {
				return false;
			}
			$result['username'] = ($token->claims()->has('name')) ? $token->claims()->get('name') : 'N/A';
			$result['group'] = ($token->claims()->has('group')) ? $token->claims()->get('group') : 'N/A';
			$result['groupID'] = $token->claims()->get('groupID');
			$result['userID'] = $token->claims()->get('userID');
			$result['email'] = $token->claims()->get('email');
			$result['image'] = $token->claims()->get('image');
			$result['tokenExpire'] = $token->claims()->get('exp');
			$result['tokenDate'] = $token->claims()->get('iat');
			return $result;
		} catch (\OutOfBoundsException | \RunTimeException | \InvalidArgumentException | \Lcobucci\JWT\Validation\RequiredConstraintsViolated $e) {
			$this->setLoggerChannel('Token Error')->error($e);
			return false;
		}
	}
}