<?php
return array(
	'SEASONAL-enabled' => false,
	// Every setting ends in -include: the browser decides which season is showing (it knows the local date)
	// Who sees the themes: both, users (logged in) or guests
	'SEASONAL-audience-include' => 'both',
	// auto = by date; a season key shows that season all the time (to try it out)
	'SEASONAL-force-include' => 'auto',
	// Corner scene (bottom left) and the figure along the bottom edge
	'SEASONAL-effects-include' => true,
	// Amount of snow, confetti and the like around the corner figure: low, medium or high
	'SEASONAL-intensity-include' => 'medium',
	// Corner scene also on top of opened website (iFrame) tabs
	'SEASONAL-overTabs-include' => false,
	'SEASONAL-decorations-include' => true,
	'SEASONAL-greeting-include' => true,
	// Users may switch the effects off for themselves in their user menu
	'SEASONAL-userToggle-include' => true,
	// Seasons: on/off and dates as MM-DD or MM-DD - MM-DD (Easter, SysAdmin Day and Programmers' Day are calculated)
	'SEASONAL-newyear-include' => true,
	'SEASONAL-newyear-dates-include' => '12-31 - 01-01',
	'SEASONAL-valentine-include' => true,
	'SEASONAL-valentine-dates-include' => '02-14',
	'SEASONAL-easter-include' => true,
	'SEASONAL-aprilfools-include' => true,
	'SEASONAL-aprilfools-dates-include' => '04-01',
	'SEASONAL-kingsday-include' => false,
	'SEASONAL-kingsday-dates-include' => '04-27',
	'SEASONAL-sysadminday-include' => true,
	'SEASONAL-programmersday-include' => true,
	'SEASONAL-halloween-include' => true,
	'SEASONAL-halloween-dates-include' => '10-24 - 10-31',
	'SEASONAL-christmas-include' => true,
	'SEASONAL-christmas-dates-include' => '12-01 - 12-26'
);
