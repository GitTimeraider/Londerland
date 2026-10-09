<?php
$GLOBALS['londerlandPages'][] = 'login';
function get_page_login($Londerland)
{
	if (!$Londerland) {
		$Londerland = new Londerland();
	}
	if ((!$Londerland->hasDB())) {
		return false;
	}
	// LONDERLAND_LOGIN_ALLOWED_IPS: visitors from other networks get a notice instead of the form
	if (!$Londerland->loginAllowedFromNetwork()) {
		return '
<section id="wrapper" class="login-register">
	<div class="login-box login-sidebar animated fadeIn">
		<div class="white-box">
			' . $Londerland->logoOrText() . '
			<div class="text-center m-t-40">
				<i class="fa fa-ban fa-4x text-danger"></i>
				<h3 class="m-t-20" lang="en">Login not allowed</h3>
				<p class="text-muted" lang="en">Logging in is not allowed from your network.</p>
				<p class="text-muted"><small><span lang="en">Your address</span>: ' . htmlspecialchars($Londerland->loginClientIP()) . '</small></p>
			</div>
		</div>
	</div>
</section>
';
	}
	$hideLonderlandLogin = ($Londerland->checkoAuth()) ? 'collapse' : 'collapse show';
	$hideLonderlandLoginHeader = ($Londerland->checkoAuthOnly()) ? 'hidden' : '';
	$hideLonderlandLoginHeader2 = ($Londerland->checkoAuth()) ? '' : 'hidden';
	$hideLonderlandRecoveryPassword = ($Londerland->config['disableRecoverPass']) ? 'hidden' : '';
	$customForgotPasswordText = (empty($Londerland->config['customForgotPassText'])) ? 'Enter your Email and instructions will be sent to you!' : $Londerland->config['customForgotPassText'];
	$customForgotPasswordText = ($Londerland->config['disableRecoverPass']) ? 'Disabled' : $customForgotPasswordText;
	$oidcAutoRedirectScript = '';
	if ($Londerland->shouldAutoRedirectToOIDC()) {
		$provider = $Londerland->getAutoRedirectProvider();
		$oidcAutoRedirectScript = '
// OIDC Auto-redirect
if (!window.location.hash.includes("noredirect") && !sessionStorage.getItem("oidc_no_redirect")) {
	window.location.href = "api/v2/oidc/' . htmlspecialchars($provider) . '/authorize";
}
';
	}
	return '
<script>
if(activeInfo.settings.login.rememberMe){
	$(\'#checkbox-login\').prop(\'checked\',true);
}
' . $oidcAutoRedirectScript . '
</script>
<section id="wrapper" class="login-register">
	<div class="login-box login-sidebar animated fadeIn">
		<div class="white-box">
			<form class="form-horizontal" id="loginform" onsubmit="return false;">
				<input id="login-attempts" class="form-control" name="loginAttempts" type="hidden">
				' . $Londerland->logoOrText() . '
				<div id="oAuth-div" class="form-group hidden">
					<div class="col-12">
						<div class="card card-success animated tada">
							<div class="card-header">oAuth Successful - Please wait...</div>
						</div>
					</div>
				</div>
				<div id="tfa-div" class="form-group hidden">
					<div class="col-12">
						<div class="card card-warning animated tada">
							<div class="card-header"> 2FA
								<div class="float-end"><a href="#" data-perform="card-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="card-dismiss"><i class="ti-close"></i></a> </div>
							</div>
							<div class="card-wrapper collapse show" aria-expanded="true">
								<div class="card-body">
									<div class="input-group" style="width: 100%;">
										<div class="input-group-text hidden-xs"><i class="ti-lock"></i></div>
										<input type="text" class="form-control tfa-input" name="tfaCode" placeholder="Code" data-lpignore="true" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="9" spellcheck="false" autofocus="">
									</div>
									<input type="hidden" name="tfaBypassRequest" value="">
									<button class="btn btn-warning btn-lg w-100 text-uppercase waves-effect waves-light login-button m-t-10" type="submit" lang="en">Login</button>
									<p class="text-muted m-t-10 m-b-0"><small><span lang="en">Lost your authenticator?</span> <a href="javascript:void(0)" class="tfa-bypass-request text-muted" style="text-decoration: underline" lang="en">Get a one-time code from your admin</a></small></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card-stack" id="login-panels" data-type="accordion" aria-multiselectable="true" role="tablist">
					<!-- LONDERLAND LOGIN -->
					<div class="card">
						<div class="card-header bg-org ' . $hideLonderlandLoginHeader . ' ' . $hideLonderlandLoginHeader2 . '" id="londerland-login-heading" role="tab">
							<a class="card-title collapsed" data-bs-toggle="collapse" href="#londerland-login-collapse" data-bs-parent="#login-panels" aria-expanded="false" aria-controls="londerland-login-collapse">
								<img class="lazyload loginTitle" data-src="plugins/images/londerland/logo-no-border.png"> &nbsp;
								<span class="text-uppercase fw300" lang="en">Login with Londerland</span>
							</a>
							<div class="clearfix"></div>
						</div>
						<div class="card-collapse ' . $hideLonderlandLogin . '" id="londerland-login-collapse" aria-labelledby="londerland-login-heading" role="tabpanel">
							<div class="card-body">
							
								<div class="form-group">
									<div class="col-12">
										<input id="login-username-Input" class="form-control" name="username" type="text" required="" placeholder="Username" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="en" autofocus>
									</div>
								</div>
								<div class="form-group">
									<div class="col-12">
										<input id="login-password-Input" class="form-control" name="password" type="password" required="" placeholder="Password" lang="en">
									</div>
								</div>
								<div class="form-group">
									<div class="col-lg-12 login-options">
										<div class="checkbox checkbox-primary float-start p-t-0 remember-me">
											<input id="checkbox-login" name="remember" type="checkbox">
											<label for="checkbox-login" lang="en">Remember Me</label>
										</div>
									</div>
								</div>
								<div class="form-group text-center m-t-20 m-b-0">
									<div class="col-12">
										<button class="btn btn-info btn-lg w-100 text-uppercase waves-effect waves-light login-button" type="submit" lang="en">Login</button>
									</div>
								</div>
								<div class="form-group m-b-0">
									<div class="col-md-12 text-center">
										<input id="oAuth-Input" class="form-control" name="oAuth" type="hidden">
										<input id="oAuthType-Input" class="form-control" name="oAuthType" type="hidden">
										' . $Londerland->showLogin() . '
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- END LONDERLAND LOGIN -->
					<!-- PLEX OAUTH LOGIN -->
					' . $Londerland->showoAuth() . '
					<!-- END PLEX OAUTH LOGIN -->
					<!-- OIDC SSO LOGIN -->
					' . $Londerland->showoAuthOIDC() . '
					<!-- END OIDC SSO LOGIN -->
				</div>
			</form>
			<form class="form-horizontal form-material hidden" id="registerForm" onsubmit="return false;">
				<div class="form-group m-t-40">
					<div class="col-12">
						<input class="form-control" type="text" name="registrationPassword" required="" placeholder="Registration Password" lang="en" autofocus>
					</div>
				</div>
				<div class="form-group">
					<div class="col-12">
						<input class="form-control" name="username" type="text" required="" placeholder="Username" lang="en">
					</div>
				</div>
				<div class="form-group">
					<div class="col-12">
						<input class="form-control" name="email" type="text" required="" placeholder="Email" lang="en">
					</div>
				</div>
				<div class="form-group">
					<div class="col-12">
						<input class="form-control" name="password" type="password" required="" placeholder="Password" lang="en">
					</div>
				</div>
				<div class="form-group text-center m-t-20">
					<div class="col-12">
						<button class="btn btn-info btn-lg w-100 text-uppercase waves-effect waves-light register-button" type="submit" lang="en">Register</button>
					</div>
				</div>
				<div class="form-group text-center m-t-20">
					<div class="col-12">
						<button id="leave-registration" class="btn btn-primary btn-lg w-100 text-uppercase waves-effect waves-light" type="button" lang="en">Go Back</button>
					</div>
				</div>
			</form>
			<form class="form-horizontal" id="recoverform" onsubmit="return false;">
				<div class="form-group ">
					<div class="col-12">
						<h3 lang="en">Recover Password</h3>
						<p class="text-muted" lang="en">' . $customForgotPasswordText . '</p>
					</div>
				</div>
				<div class="form-group ' . $hideLonderlandRecoveryPassword . '">
					<div class="col-12">
						<input id="recover-input" class="form-control" name="email" type="text" placeholder="Email" lang="en" required>
					</div>
				</div>
				<div class="form-group text-center m-t-20 ' . $hideLonderlandRecoveryPassword . '">
					<div class="col-12">
						<button class="btn btn-primary btn-lg w-100 text-uppercase waves-effect waves-light reset-button" type="submit" lang="en">Reset</button>
					</div>
				</div>
				<div class="form-group text-center m-t-20">
					<div class="col-12">
						<button id="leave-recover" class="btn btn-primary btn-lg w-100 text-uppercase waves-effect waves-light" type="button" lang="en">Go Back</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>
';
}