<?php
return array(
	'ACHIEVEMENTS-enabled' => false,
	// Settings ending in -include are sent to the browser
	// Group IDs that collect achievements and get the My Stats page (comma separated); 'auto' = every group except guests
	'ACHIEVEMENTS-groups-include' => 'auto',
	// Pop-up message when a badge is unlocked
	'ACHIEVEMENTS-notify-include' => true,
	// Top 10 of all users on the My Stats page
	'ACHIEVEMENTS-leaderboard-include' => false,
	// Achievement keys that are switched off (comma separated)
	'ACHIEVEMENTS-disabled' => '',
	// Set by the plugin: its table exists (version), and AI Chat's tables exist; saves checking on every request
	'ACHIEVEMENTS-dbVersion' => '',
	'ACHIEVEMENTS-aiTables' => false
);
