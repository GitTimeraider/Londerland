<?php
$GLOBALS['londerlandPages'][] = 'settings_tab_editor_homepage';
function get_page_settings_tab_editor_homepage($Londerland)
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
	buildHomepage();
</script>
<div class="card bg-org card-info">
    <div class="card-header">
		<span lang="en">Homepage Items</span>
	</div>
    <div class="card-wrapper collapse show" aria-expanded="true">
        <div class="card-body bg-org" >
        	<div class="row el-element-overlay m-b-40" id="settings-homepage-list">
        		<div class="text-center"><i class="fa fa-spin fa-spinner fa-3x"></i></div>
			</div>
        </div>
    </div>
</div>

';
}
