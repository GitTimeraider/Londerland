<?php

trait MiscHomepageItem
{
	public function miscSettingsArray($infoOnly = false)
	{
		$homepageInformation = [
			'name' => 'Misc',
			'enabled' => true,
			'image' => 'plugins/images/londerland/logo-no-border.png',
			'category' => 'Custom',
			'settingsArray' => __FUNCTION__
		];
		if ($infoOnly) {
			return $homepageInformation;
		}
		$homepageSettings = [
			'debug' => true,
			'settings' => [
				'YouTube' => [
					$this->settingsOption('token', 'youtubeAPI', ['label' => 'Youtube API Key', 'help' => 'Your own YouTube Data API v3 key from Google Cloud. Needed for playing trailers; without it, trailers open as a YouTube search']),
					$this->settingsOption('html', null, ['override' => 6, 'label' => 'Instructions', 'html' => '<a href="https://www.slickremix.com/docs/get-api-key-for-youtube/" target="_blank">Click here for instructions</a>']),
				]
			]
		];
		return array_merge($homepageInformation, $homepageSettings);
	}
	
}