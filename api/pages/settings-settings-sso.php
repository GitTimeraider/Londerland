<?php
$GLOBALS['londerlandPages'][] = 'settings_settings_sso';
function get_page_settings_settings_sso($Londerland)
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
	buildSSO();
</script>
<div class="card bg-org card-info">
    <div class="card-header">
		<span lang="en">Single Sign-On</span>
		<button id="sso-form-save" onclick="submitSettingsForm(\'sso-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light float-end hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="card-wrapper collapse show" aria-expanded="true">
        <div class="bg-org">
            <form id="sso-form" class="addFormTick" onsubmit="return false;"></form>
        </div>
    </div>
</div>
';
}