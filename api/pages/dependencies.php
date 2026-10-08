<?php
$GLOBALS['londerlandPages'][] = 'dependencies';
function get_page_dependencies($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	return '
<script>
</script>
<div class="container-fluid">
	<div class="row bg-title">
		<div class="col-xl-3 col-lg-4 col-md-4 col-12">
			<h4 class="page-title" lang="en">Londerland Dependency Check</h4>
		</div>
		<!-- /.col-xl-12 -->
	</div>
	<!--.row-->
	<div class="row">
		<div class="col-md-12">
			<div class="white-box">
				<div class="row row-in">
					<div class="col-xl-4 col-md-6 row-in-br">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-dependencies-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-end m-t-15" lang="en">Dependencies</h3>
							</li>
							
						</ul>
					</div>
					<div class="col-xl-4 col-md-6 row-in-br  b-r-none">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-phpversion-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-end m-t-15" lang="en">PHP Version</h3>
							</li>
							
						</ul>
					</div>
					
					<div class="col-xl-4 col-md-6  b-0">
						<ul class="col-in">
							<li>
								<span class="circle circle-md bg-warning dependency-permissions-check"><i class="fa fa-spin fa-spinner"></i></span>
							</li>
							<li class="col-last">
								<h3 class="counter text-end m-t-15" lang="en">Permissions</h3>
							</li>
							
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xl-4 col-md-6">
			<div class="card card-danger dependency-dependencies-check-listing-header">
				<div class="card-header dependency-dependencies-check-listing"> <i class="ti-alert fa-fw"></i> <span lang="en">Dependencies Missing</span>
					<div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="card-wrapper collapse show" aria-expanded="true">
					<div class="card-body">
						<ul class="common-list" id="depenency-info"></ul>
					</div>
				</div>
			</div>
		</div>
		<div class="col-xl-4 col-md-6">
			<div class="card card-info">
				<div class="card-header"> <i class="ti-alert fa-fw"></i> <span lang="en">PHP Version Check</span>
					<div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="card-wrapper collapse show" aria-expanded="true">
					<table class="table table-hover">
						<tbody>
							<tr>
								<td id="php-version-check" lang="en">Loading...</td>
							</tr>
							<tr>
								<td id="php-version-check-user" lang="en">Loading...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-xl-4 col-md-6">
			<div class="card card-info">
				<div class="card-header"> <i class="ti-alert fa-fw"></i> <span lang="en">Web Folder</span>
					<div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a></div>
				</div>
				<div class="card-wrapper collapse show" aria-expanded="true">
					<table class="table table-hover">
						<tbody>
							<tr>
								<td>' . dirname(__DIR__, 2) . '</td>
							</tr>
							<tr>
								<td id="web-folder" lang="en">Loading...</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-xl-12">
			<div class="card card-info">
				<div class="card-header"> <i class="ti-alert fa-fw"></i> <span lang="en">Browser Information</span>
					<div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-plus"></i></a></div>
				</div>
				<div class="card-wrapper collapse" id="browser-info" aria-expanded="false"></div>
			</div>
		</div>

	</div>
	<!--./row-->
</div>
<!-- /.container-fluid -->
';
}