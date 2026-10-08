<?php

class LonderlandDigestAuth extends \WpOrg\Requests\Auth\Basic
{
	
	/**
	 * Set cURL parameters before the data is sent
	 *
	 * @param resource $handle cURL resource
	 */
	public function curl_before_send(&$handle)
	{
		curl_setopt($handle, CURLOPT_USERPWD, $this->getAuthString());
		curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY); //CURLAUTH_ANY work with Wowza RESTful
	}
}
