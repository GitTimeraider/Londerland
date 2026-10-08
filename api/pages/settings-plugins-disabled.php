<?php
$GLOBALS['londerlandPages'][] = 'settings_plugins_disabled';
function get_page_settings_plugins_disabled($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	if (!$Londerland->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	buildPlugins("disabled");
</script>
<div id="disabled-plugin-area"></div>
';
}