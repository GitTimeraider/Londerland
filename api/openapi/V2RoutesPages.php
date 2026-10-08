<?php

// OpenAPI documentation for the routes in api/v2/routes/pages.php (collected by docs/index.php)

namespace Londerland\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(
	name: 'page',
	description: 'HTML for Londerland Pages',
)]
#[OA\Schema(
	schema: 'get-html',
	properties: [
		new OA\Property(
			properties: [
				new OA\Property(
					description: 'success or error',
					type: 'string',
					example: 'success',
					property: 'result',
				),
				new OA\Property(
					description: 'success message or error message',
					type: 'string',
					example: null,
					property: 'message',
				),
				new OA\Property(
					description: 'data from api',
					type: 'string',
					example: '\\r\\n\\u003Cscript\\u003E\\r\\n    (function() {\\r\\n        authDebugCheck();\\r\\n        [].slice.call(document.querySelectorAll(\'.sttabs-main-settings-div\')).forEach(function(el) {\\r\\n            new CBPFWTabs(el);\\r\\n        });\\r\\n    })();\\r\\n\\u003C/script\\u003E\\r\\n',
					property: 'data',
				),
			],
			type: 'object',
			property: 'response',
		),
	],
	type: 'object',
)]
#[OA\Get(
	path: '/api/v2/page/{page}',
	tags: [
		'page',
	],
	summary: 'Get HTML for Londerland Pages',
	parameters: [
		new OA\Parameter(
			name: 'page',
			in: 'path',
			description: 'Page to get',
			required: true,
			schema: new OA\Schema(
				type: 'string',
			),
		),
	],
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/get-html',
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
	path: '/api/v2/page',
	tags: [
		'page',
	],
	summary: 'Get list of all Londerland Pages',
	responses: [
		new OA\Response(
			response: '200',
			description: 'Success',
			content: new OA\JsonContent(
				ref: '#/components/schemas/get-html',
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
final class V2RoutesPages
{
}
