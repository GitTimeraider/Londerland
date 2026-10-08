<?php

// OpenAPI documentation for the routes in api/plugins/php-mailer/api.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'plugins-php-mailer',
	description: 'PHP Mailer Plugin',
)]
#[OA\Schema(
	schema: 'sendEmailData',
	properties: [
		new OA\Property(
			description: 'email of recipients (csv)',
			type: 'string',
			example: 'user@example.com,friend@example.com',
			property: 'bcc',
		),
		new OA\Property(
			type: 'string',
			example: 'Hey There Buddy?!',
			property: 'subject',
		),
		new OA\Property(
			type: 'string',
			example: 'Hi! Boy, has it been a long time!  Have you seen rox in socks?',
			property: 'body',
		),
	],
	type: 'object',
)]
#[OA\Get(
	path: '/api/v2/plugins/php-mailer/settings',
	tags: [
		'plugins-php-mailer',
	],
	summary: 'Get settings',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/pluginSettingsPage',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/plugins/php-mailer/email/test',
	tags: [
		'plugins-php-mailer',
	],
	summary: 'Send Test Email to Default Admin Email',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/successNullData',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Post(
	path: '/api/v2/plugins/php-mailer/email/send',
	tags: [
		'plugins-php-mailer',
	],
	summary: 'Send Email',
	requestBody: new OA\RequestBody(
		description: 'Success',
		required: true,
		content: new OA\JsonContent(
			ref: '#/components/schemas/sendEmailData',
		),
	),
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/successNullData',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
#[OA\Get(
	path: '/api/v2/plugins/php-mailer/email/list',
	tags: [
		'plugins-php-mailer',
	],
	summary: 'Get List of User Emails',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/php-mailer-email-list',
			),
		),
		new OA\Response(
			response: '401',
			description: 'Unauthorized',
		),
	],
	security: [
		[
			'api_key' => [],
		],
	],
)]
final class PluginsPhpMailerApi
{
}
