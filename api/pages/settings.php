<?php
$GLOBALS['londerlandPages'][] = 'settings';
function get_page_settings($Londerland)
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
	$Londerland->setLoggerChannel('Londerland');
	$Londerland->logger->notice('Accessed admin settings page');
	$systemMenus = $Londerland->systemMenuLists();
	return $Londerland->pluginFiles('js', true) . '
<script>
	(function() {
		authDebugCheck();
		[].slice.call(document.querySelectorAll(\'.sttabs-main-settings-div\')).forEach(function(el) {
			new CBPFWTabs(el);
		});
	})();
</script>
<div class="container-fluid">
	<div class="row bg-title">
		<div class="col-xl-3 col-lg-4 col-md-4 col-12">
			<h4 class="page-title" lang="en">Londerland Settings</h4>
		</div>
		<div class="col-xl-9 col-md-8 col-lg-8 col-12">
			<ol id="settingsBreadcrumb" class="breadcrumb">
				<li lang="en">Settings</li>
				<li lang="en">Tab Editor</li>
			</ol>
		</div>
		<!-- /.col-xl-12 -->
	</div>
	<!--.row-->
	<div class="row">
		<!-- Tab style start -->
		<section class="">
			<div class="sttabs sttabs-main-settings-div tabs-style-flip">
				<nav>
					<ul>
						<li onclick="changeSettingsMenu(\'Settings::Tab Editor\')" id="settings-main-tab-editor-anchor"><a href="#settings-main-tab-editor" class="sticon ti-layout-tab-v"><span lang="en">Tab Editor</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Customize\')" id="settings-main-customize-anchor"><a href="#settings-main-customize" class="sticon ti-paint-bucket"><span lang="en">Customize</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::User Management\')" id="settings-main-user-management-anchor"><a href="#settings-main-user-management" class="sticon ti-user"><span lang="en">User Management</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Image Manager\');loadSettingsPage2(\'api/v2/page/settings_image_manager\',\'#settings-image-manager-view\',\'Image Viewer\');" id="settings-main-image-manager-anchor"><a href="#settings-main-image-manager" class="sticon ti-image"><span lang="en">Image Manager</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::Plugins\')" id="settings-main-plugins-anchor"><a href="#settings-main-plugins" class="sticon ti-plug"><span lang="en">Plugins</span></a></li>
						<li onclick="changeSettingsMenu(\'Settings::System Settings\');authDebugCheck();" id="settings-main-system-settings-anchor"><a href="#settings-main-system-settings" class="sticon ti-settings"><span lang="en">System Settings</span></a></li>
					</ul>
				</nav>
				<div class="content-wrap">
					<! -- TAB EDITOR -->
					<section id="settings-main-tab-editor">
						' . $systemMenus['tab_editor'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-tabs">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-tab-editor-categories">
								<h2 lang="en">Loading...</h2>
							</div>
						</div>
					</section>
					<! -- Customize -->
					<section id="settings-main-customize">
						' . $systemMenus['customize'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-customize-appearance">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- USER MANAGEMENT -->
					<section id="settings-main-user-management">
						' . $systemMenus['user_management'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-user-manage-users">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-user-manage-groups">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-user-import-users">
								' . $Londerland->importUserButtons() . '
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- IMAGE MANAGER -->
					<section id="settings-main-image-manager">
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade active show" id="settings-image-manager-view">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- PLUGINS -->
					<section id="settings-main-plugins">
						' . $systemMenus['plugins'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-plugins-enabled">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-plugins-disabled">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
					<! -- SYSTEM SETTINGS -->
					<section id="settings-main-system-settings">
					' . $systemMenus['system_settings'] . '
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-main">
								<h2 lang="en">Main Settings</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-sso">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-logs">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade" id="settings-settings-backup">
								<h2 lang="en">Loading...</h2>
								<div class="clearfix"></div>
							</div>
							<div role="tabpanel" class="tab-pane fade active show" id="settings-settings-about">
								<div class="row">
									<div class="col-xl-6 col-md-12 col-lg-6">
										<div class="card bg-org">
											<div class="p-30">
												<div class="row">
													<div class="col-12"><img src="' . htmlspecialchars(trim($Londerland->config['aboutLogo'] ?? '') ?: 'plugins/images/londerland/logo-wide.png') . '" alt="Londerland" class="img-fluid"></div>
												</div>
											</div>
											<hr class="m-t-10">
											<div class="p-20">
												<p lang="en">Londerland brings all of your web applications together on one page, behind a single login.</p>
												<p class="m-b-0"><small class="text-muted" lang="en">Free software under the GNU General Public License v3.0.</small></p>
											</div>
										</div>
									</div>
									<div class="col-xl-6 col-md-12 col-lg-6">
										<div class="white-box bg-org">
											<h3 class="box-title" lang="en">Information</h3>
											<ul class="feeds">
												<li><div class="bg-info"><i class="mdi mdi-webpack mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Londerland Version</span> ' . $Londerland->version . '</li>
												<li><div class="bg-info"><i class="mdi mdi-database mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Database Driver</span> ' . $Londerland->config['driver'] . '&nbsp;<code><i class="fa fa-arrow-right"></i></code>&nbsp;<small>' . $Londerland->config['dbName'] . '</small></li>
												' . $Londerland->settingsDocker() . $Londerland->settingsPathChecks() . '
												<hr class="m-t-10">
												<li><div class="bg-info"><i class="mdi mdi-language-php mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">PHP Version</span> ' . phpversion() . '</li>
												<li><div class="bg-info"><i class="mdi mdi-package-variant-closed mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Webserver Version</span> ' . $_SERVER['SERVER_SOFTWARE'] . '</li>
												<hr class="m-t-10">
												<li><div class="bg-info"><i class="mdi mdi-card-account-details mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">License</span> ' . ucwords($Londerland->config['license']) . '</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="clearfix"></div>
							</div>
						</div>
					</section>
				</div>
				<!-- /content -->
			</div>
			<!-- /tabs -->
		</section>
	</div>
	<!--./row-->
</div>
<!-- /.container-fluid -->
';
}