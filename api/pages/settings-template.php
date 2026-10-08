<?php
$GLOBALS['londerlandPages'][] = 'settings_template';
function get_page_settings_template($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	/*
	 * Take this out if you dont care if DB as been created
	 */
	if ((!$Londerland->hasDB())) {
		return false;
	}
	/*
	 * Take this out if you dont want to be for admin only
	 */
	if (!$Londerland->qualifyRequest(1, true)) {
		return false;
	}
	return '
<script>
	// Custom JS here
</script>
<div class="card bg-org card-info">
    <div class="card-header">
		<span lang="en">Template</span>
	</div>
    <div class="card-wrapper collapse show" aria-expanded="true">
        <div class="card-body bg-org">
        </div>
    </div>
</div>
';
}