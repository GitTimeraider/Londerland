<?php
$GLOBALS['organizrPages'][] = 'settings_plugins_settings';
function get_page_settings_plugins_settings($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	if ((!$Organizr->hasDB())) {
		return false;
	}
	if (!$Organizr->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	buildPluginsSettings();
</script>
<div class="card bg-org card-info">
    <div class="card-header">
		<span lang="en">Plugin Settings</span>
		<button type="button" id="customize-appearance-reload" class="btn btn-primary btn-circle float-end reload hidden m-r-5"><i class="fa fa-spin fa-refresh"></i> </button>
		<button id="plugin-settings-form-save" onclick="submitSettingsForm(\'plugin-settings-form\')" class="btn btn-sm btn-info btn-rounded waves-effect waves-light float-end hidden animated loop-animation rubberBand" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save</span></button>
	</div>
    <div class="card-wrapper collapse show" aria-expanded="true">
        <div class="bg-org">
            <form id="plugin-settings-form" class="addFormTick" onsubmit="return false;"></form>
        </div>
    </div>
</div>
';
}