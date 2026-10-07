<?php

// OpenAPI documentation for the routes in api/v2/routes/connectionTester.php (collected by docs/index.php)

namespace Organizr\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'test connection',
	description: 'Test Connections',
)]
#[OA\Post(
	path: '/api/v2/test/ldap',
	tags: [
		'test connection',
	],
	summary: 'Test LDAP connection',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '409',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/ldap/login',
	tags: [
		'test connection',
	],
	summary: 'Test LDAP connection using account login',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '409',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/iframe',
	tags: [
		'test connection',
	],
	summary: 'Test if URL can be iFramed',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '409',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/path',
	tags: [
		'test connection',
	],
	summary: 'Test if path has correct permissions',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '404',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/plex',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Plex',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/emby',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Emby',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/jellyfin',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Jellyfin',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/sabnzbd',
	tags: [
		'test connection',
	],
	summary: 'Test connection to SabNZBd',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/pihole',
	tags: [
		'test connection',
	],
	summary: 'Test connection to PiHole',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/adguard',
	tags: [
		'test connection',
	],
	summary: 'Test connection to AdGuard',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/rtorrent',
	tags: [
		'test connection',
	],
	summary: 'Test connection to rTorrent',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/sonarr',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Sonarr',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/radarr',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Radarr',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/lidarr',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Lidarr',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/sickrage',
	tags: [
		'test connection',
	],
	summary: 'Test connection to SickRage',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/ombi',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Ombi',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/overseerr',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Overseerr',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/nzbget',
	tags: [
		'test connection',
	],
	summary: 'Test connection to NzbGet',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/utorrent',
	tags: [
		'test connection',
	],
	summary: 'Test connection to uTorrent',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '400',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/deluge',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Deluge',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/jdownloader',
	tags: [
		'test connection',
	],
	summary: 'Test connection to jDownloader',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/transmission',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Transmission',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/qbittorrent',
	tags: [
		'test connection',
	],
	summary: 'Test connection to qBittorrent',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/unifi',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Unifi',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/unifi/site',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Unifi Sites',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/tautulli',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Tautulli',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/cron',
	tags: [
		'test connection',
	],
	summary: 'Test cron schedule',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/test/cron',
	tags: [
		'test connection',
	],
	summary: 'Test if cron is setup correctly',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/folder',
	tags: [
		'test connection',
	],
	summary: 'Test folder path',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/database',
	tags: [
		'test connection',
	],
	summary: 'Test Database connection',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/jackett',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Jackett',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/prowlarr',
	tags: [
		'test connection',
	],
	summary: 'Test connection to prowlarr',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/slack-logs',
	tags: [
		'test connection',
	],
	summary: 'Test connection to Slack/Discord',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/test/jellystat',
	tags: [
		'test connection',
	],
	summary: 'Test connection to JellyStat',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/success-message',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
			content: new OA\JsonContent(
				ref: '#/components/schemas/unauthorized-message',
			),
		),
		new OA\Response(
			response: '422',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
		new OA\Response(
			response: '500',
			description: 'Error',
			content: new OA\JsonContent(
				ref: '#/components/schemas/error-message',
			),
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
final class V2RoutesConnectionTester
{
}
