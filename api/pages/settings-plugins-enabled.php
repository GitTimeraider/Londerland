<?php
$GLOBALS['londerlandPages'][] = 'settings_plugins_enabled';
function get_page_settings_plugins_enabled($Londerland)
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
	buildPlugins("enabled");
</script>
<div id="enabled-plugin-area"></div>
<form id="about-plugin-form" class="mfp-hide white-popup-block mfp-with-anim">
    <h2 id="about-plugin-title">Loading...</h2>
    <div class="clearfix"></div>
    <div id="about-plugin-body" class=""></div>
</form>
';
}