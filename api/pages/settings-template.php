<?php
$GLOBALS['organizrPages'][] = 'settings_template';
function get_page_settings_template($Organizr)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	/*
	 * Take this out if you dont care if DB as been created
	 */
	if ((!$Organizr->hasDB())) {
		return false;
	}
	/*
	 * Take this out if you dont want to be for admin only
	 */
	if (!$Organizr->qualifyRequest(1, true)) {
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