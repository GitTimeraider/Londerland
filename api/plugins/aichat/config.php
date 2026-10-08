<?php
return array(
	'AICHAT-enabled' => false,
	// Settings ending in -include are sent to the browser; everything else (API key, prompts) stays on the server
	'AICHAT-Auth-include' => '4',
	// Group IDs that get the chat (comma separated); 'auto' = AICHAT-Auth-include and higher groups, as before
	'AICHAT-groups-include' => 'auto',
	'AICHAT-uploads-include' => true,
	'AICHAT-maxUploadMB-include' => '20',
	// Chat button: name under the icon and colour (empty = theme colour)
	'AICHAT-launcherLabel-include' => 'AI',
	'AICHAT-launcherColor-include' => '',
	'AICHAT-baseUrl' => '',
	'AICHAT-apiKey' => '',
	'AICHAT-defaultModel' => '',
	'AICHAT-allowedModels' => '',
	'AICHAT-extraModels' => '',
	'AICHAT-systemPrompt' => '',
	'AICHAT-temperature' => '',
	'AICHAT-maxTokens' => '',
	'AICHAT-contextMessages' => '40',
	'AICHAT-autoTitle' => true,
	'AICHAT-requestTimeout' => '300',
	'AICHAT-verifySSL' => true,
	// Web search: none, searxng, brave, tavily or duckduckgo
	'AICHAT-searchProvider-include' => 'none',
	'AICHAT-searchUrl' => '',
	'AICHAT-searchApiKey' => '',
	'AICHAT-searchResults' => '5',
	'AICHAT-searchAuto' => false,
	// Image generation through an OpenAI-compatible /images/generations endpoint
	'AICHAT-images-include' => false,
	'AICHAT-imageBaseUrl' => '',
	'AICHAT-imageApiKey' => '',
	'AICHAT-imageModel' => 'gpt-image-1',
	'AICHAT-imageSize' => '1024x1024',
	'AICHAT-imageAuto' => false
);
