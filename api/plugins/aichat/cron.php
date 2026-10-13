<?php
/*
 * Hourly job: deletes chats that passed the retention period (Chat > Delete Chats After)
 */
$GLOBALS['cron'][] = [
	'class' => 'AiChat', // Class name of plugin (case-sensitive)
	'enabled' => 'AICHAT-cron-cleanup-enabled', // Config item for job enable
	'schedule' => 'AICHAT-cron-cleanup-schedule', // Config item for job schedule
	'function' => '_aiChatRetentionCron', // Function to run during job
];
