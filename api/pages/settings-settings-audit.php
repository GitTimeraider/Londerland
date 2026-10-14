<?php
$GLOBALS['londerlandPages'][] = 'settings_settings_audit';
function get_page_settings_settings_audit($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	if (!$Londerland->qualifyRequest(0, true)) {
		return false;
	}
	$enabled = !empty($Londerland->config['auditLogEnabled']) ? ' checked' : '';
	$days = (int)($Londerland->config['auditLogDays'] ?? 365);
	return '
<script>
	buildAuditLog();
</script>
<div class="white-box bg-org audit-log">
	<div class="audit-log-head">
		<h3 class="box-title m-b-0" lang="en">Audit Log</h3>
		<div class="audit-log-actions">
			<a class="btn btn-sm btn-secondary waves-effect" id="audit-log-export" href="api/v2/audit/export" target="_blank" rel="noopener"><i class="fa fa-download m-r-5"></i><span lang="en">Export CSV</span></a>
			<button class="btn btn-sm btn-danger waves-effect" type="button" onclick="clearAuditLog()"><i class="fa fa-trash m-r-5"></i><span lang="en">Clear</span></button>
		</div>
	</div>
	<p class="text-muted" lang="en">Who signed in, and who changed settings, tabs, categories, users, groups, plugins, images and backups. Setting values are never stored, only their names.</p>
	<form class="audit-log-options row g-2 align-items-end m-b-20" onsubmit="saveAuditLogOptions(); return false;">
		<div class="col-auto">
			<div class="form-check form-switch m-b-5">
				<input class="form-check-input" type="checkbox" role="switch" id="audit-log-enabled"' . $enabled . '>
				<label class="form-check-label" for="audit-log-enabled" lang="en">Keep an audit log</label>
			</div>
		</div>
		<div class="col-auto">
			<label class="form-label m-b-0" for="audit-log-days" lang="en">Keep entries (days, 0 = forever)</label>
			<input class="form-control form-control-sm" type="number" min="0" max="3650" id="audit-log-days" value="' . $days . '">
		</div>
		<div class="col-auto">
			<button class="btn btn-sm btn-info" type="submit" lang="en">Save</button>
		</div>
	</form>
	<div class="audit-log-filters row g-2 m-b-10">
		<div class="col-md-6">
			<input class="form-control" type="search" id="audit-log-search" placeholder="Search user, action, target, IP" autocomplete="off">
		</div>
		<div class="col-md-4">
			<select class="form-select" id="audit-log-category">
				<option value="" lang="en">Everything</option>
				<option value="auth" lang="en">Sign-ins</option>
				<option value="settings" lang="en">Settings</option>
				<option value="tab" lang="en">Tabs</option>
				<option value="category" lang="en">Categories</option>
				<option value="user" lang="en">Users</option>
				<option value="group" lang="en">Groups</option>
				<option value="plugin" lang="en">Plugins</option>
				<option value="image" lang="en">Images</option>
				<option value="backup" lang="en">Backups</option>
				<option value="audit" lang="en">Audit log</option>
			</select>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-sm audit-log-table">
			<thead><tr><th lang="en">Time</th><th lang="en">User</th><th lang="en">What</th><th lang="en">Details</th><th lang="en">IP</th></tr></thead>
			<tbody id="audit-log-rows"><tr><td colspan="5"><i class="fa fa-spin fa-spinner"></i></td></tr></tbody>
		</table>
	</div>
	<div class="audit-log-pager">
		<button class="btn btn-sm btn-secondary" type="button" id="audit-log-prev" lang="en">Newer</button>
		<span class="text-muted" id="audit-log-page"></span>
		<button class="btn btn-sm btn-secondary" type="button" id="audit-log-next" lang="en">Older</button>
	</div>
</div>
';
}
