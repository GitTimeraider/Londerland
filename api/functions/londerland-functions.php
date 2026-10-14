<?php

trait LonderlandFunctions
{

	public function loadResources($files = [], $rootPath = '')
	{
		$scripts = '';
		if (count($files) > 0) {
			foreach ($files as $file) {
				if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'js') {
					$scripts .= $this->loadJavaResource($file, $rootPath);
				} elseif (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'css') {
					$scripts .= $this->loadStyleResource($file, $rootPath);
				}
			}
		}
		return $scripts;
	}

	public function loadJavaResource($file = '', $rootPath = '')
	{
		return ($file !== '') ? '<script src="' . $rootPath . $file . '?v=' . trim($this->fileHash) . '"></script>' . "\n" : '';
	}

	public function loadStyleResource($file = '', $rootPath = '')
	{
		return ($file !== '') ? '<link href="' . $rootPath . $file . '?v=' . trim($this->fileHash) . '" rel="stylesheet">' . "\n" : '';
	}

	public function approvedFileExtension($filename, $type = 'image')
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ($type == 'image') {
			switch ($ext) {
				case 'gif':
				case 'png':
				case 'jpeg':
				case 'jpg':
					return true;
				default:
					return false;
			}
		} elseif ($type == 'cert') {
			switch ($ext) {
				case 'pem':
					return true;
				default:
					return false;
			}
		}
	}

	public function approvedFileType($file, $type = 'image')
	{
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$ext = $finfo->file($file);
		if ($type == 'image') {
			switch ($ext) {
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
				case 'image/pjpeg':
					return true;
				default:
					return false;
			}
		}
		return false;
	}

	public function getImages()
	{
		$allIconsPrep = array();
		$allIcons = array();
		$ignore = array(".", "..", "._.DS_Store", ".DS_Store", ".pydio_id", "index.html");
		$dirname = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tabs' . DIRECTORY_SEPARATOR;
		$path = 'plugins/images/tabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		$dirname = $this->root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'userTabs' . DIRECTORY_SEPARATOR;
		$path = 'data/userTabs/';
		$images = scandir($dirname);
		foreach ($images as $image) {
			if (!in_array($image, $ignore)) {
				$allIconsPrep[$image] = array(
					'path' => $path,
					'name' => $image
				);
			}
		}
		ksort($allIconsPrep);
		foreach ($allIconsPrep as $item) {
			$allIcons[] = $item['path'] . $item['name'];
		}
		return $allIcons;
	}

	public function getSounds()
	{
		$sounds = array();
		foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . "*.mp3") as $filename) {
			$sounds[] = array(
				'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
				'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', 'plugins/sounds/default/' . basename($filename) . '.mp3')
			);
		}
		foreach (glob(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'sounds' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . "*.mp3") as $filename) {
			$sounds[] = array(
				'name' => preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($filename)),
				'value' => preg_replace('/\\.[^.\\s]{3,4}$/', '', 'plugins/sounds/custom/' . basename($filename) . '.mp3')
			);
		}
		return $sounds;
	}

	public function getSettingsTabs()
	{
		return array(
			array(
				'name' => 'Tab Editor',
				'value' => '0'
			),
			array(
				'name' => 'Customize',
				'value' => '1'
			),
			array(
				'name' => 'User Management',
				'value' => '2'
			),
			array(
				'name' => 'Image Manager',
				'value' => '3'
			),
			array(
				'name' => 'Plugins',
				'value' => '4'
			),
			array(
				'name' => 'System Settings',
				'value' => '5'
			)
		);
	}

	public function getAuthTypes()
	{
		return array(
			array(
				'name' => 'Londerland DB',
				'value' => 'internal'
			),
			array(
				'name' => 'Londerland DB + Backend',
				'value' => 'both'
			),
			array(
				'name' => 'Backend Only',
				'value' => 'external'
			)
		);
	}

	public function getLDAPOptions()
	{
		return array(
			array(
				'name' => 'Active Directory',
				'value' => '1'
			),
			array(
				'name' => 'OpenLDAP',
				'value' => '2'
			),
			array(
				'name' => 'Free IPA',
				'value' => '3'
			),
		);
	}

	public function getAuthBackends()
	{
		$backendOptions = array();
		$backendOptions[] = array(
			'name' => 'Choose Backend',
			'value' => false,
			'disabled' => true
		);
		foreach (array_filter(get_class_methods('Londerland'), function ($v) {
			return strpos($v, 'plugin_auth_') === 0;
		}) as $value) {
			$name = str_replace('plugin_auth_', '', $value);
			if ($name == 'ldap') {
				if (!function_exists('ldap_connect')) {
					continue;
				}
			}
			if ($name == 'ldap_disabled') {
				if (function_exists('ldap_connect')) {
					continue;
				}
			}
			if (strpos($name, 'disabled') === false) {
				$backendOptions[] = array(
					'name' => ucwords(str_replace('_', ' ', $name)),
					'value' => $name
				);
			} else {
				$backendOptions[] = array(
					'name' => $this->$value(),
					'value' => 'none',
					'disabled' => true,
				);
			}
		}
		ksort($backendOptions);
		return $backendOptions;
	}

	public function checkFrame($array, $url)
	{
		if (array_key_exists("x-frame-options", $array)) {
			if (gettype($array['x-frame-options']) == 'array') {
				$array['x-frame-options'] = $array['x-frame-options'][0];
			}
			$array['x-frame-options'] = strtolower($array['x-frame-options']);
			if ($array['x-frame-options'] == "deny") {
				return false;
			} elseif ($array['x-frame-options'] == "sameorgin") {
				$digest = parse_url($url);
				$host = ($digest['host'] ?? '');
				if ($this->getServer() == $host) {
					return true;
				} else {
					return false;
				}
			} elseif (strpos($array['x-frame-options'], 'allow-from') !== false) {
				$explodeServers = explode(' ', $array['x-frame-options']);
				$allowed = false;
				foreach ($explodeServers as $server) {
					$digest = parse_url($server);
					$host = ($digest['host'] ?? '');
					if ($this->getServer() == $host) {
						$allowed = true;
					}
				}
				return $allowed;
			} else {
				return false;
			}
		} else {
			if (!$array) {
				return false;
			}
			return true;
		}
	}

	public function frameTest($url)
	{
		if (!$url || $url == '') {
			$this->setAPIResponse('error', 'URL not supplied', 404);
			return false;
		}
		$array = array_change_key_case(get_headers($this->qualifyURL($url), 1));
		$url = $this->qualifyURL($url);
		if ($this->checkFrame($array, $url)) {
			$this->setAPIResponse('success', 'URL approved for iFrame', 200);
			return true;
		} else {
			$this->setAPIResponse('error', 'URL failed approval for iFrame', 409);
			return false;
		}
	}

	public function groupSelect()
	{
		$groups = $this->getAllGroups();
		$select = array();
		foreach ($groups as $key => $value) {
			$select[] = array(
				'name' => $value['group'],
				'value' => $value['group_id']
			);
		}
		return $select;
	}

	public function userSelect()
	{
		$users = $this->getAllUsers();
		$select = [];
		foreach ($users as $key => $value) {
			$select[] = array(
				'name' => $value['username'],
				'value' => $value['id']
			);
		}
		return $select;
	}

	public function showLogin()
	{
		if ($this->config['hideRegistration'] == false) {
			return '<p><span lang="en">Don\'t have an account?</span><a href="#" class="text-primary m-l-5 to-register"><b lang="en">Sign Up</b></a></p>';
		}
	}

	public function logoOrText()
	{
		$showLogo = $this->config['minimalLoginScreen'] ? '' : 'visible-xs';
		if ($this->config['useLogoLogin'] == false) {
			$html = '<h1>' . $this->config['title'] . '</h1>';
		} else {
			$html = '<img class="loginLogo" src="' . $this->config['loginLogo'] . '" alt="Home" />';
		}
		return '<a href="javascript:void(0)" class="text-center db ' . $showLogo . '" id="login-logo">' . $html . '</a>';
	}

	public function showoAuthOIDC()
	{
		$buttons = '';
		$providers = $this->getEnabledOIDCProviders();
		foreach ($providers as $provider => $config) {
			$name = htmlspecialchars($this->config[$config['configPrefix'] . 'Name'] ?? ucfirst($provider));
			$buttons .= '<a href="javascript:void(0)" onclick="oidcStart(\'' . htmlspecialchars($provider) . '\')" class="btn btn-lg w-100 text-uppercase waves-effect waves-light bg-oidc-' . htmlspecialchars($provider) . ' text-muted"> <span>Login with ' . $name . '</span><i aria-hidden="true" class="mdi mdi-shield-key m-l-5"></i> </a>';
		}
		if (!$buttons) {
			return '';
		}
		return '
		<div class="card">
			<div class="card-header bg-org" id="oidc-login-heading" role="tab">
				<a class="card-title" data-bs-toggle="collapse" href="#oidc-login-collapse" data-bs-parent="#login-panels" aria-expanded="false" aria-controls="oidc-login-collapse">
					<i class="mdi mdi-shield-account"></i> &nbsp;
					<span class="text-uppercase fw300" lang="en">Single Sign-On</span>
				</a>
			</div>
			<div class="card-collapse collapse show" id="oidc-login-collapse" aria-labelledby="oidc-login-heading" role="tabpanel">
				<div class="card-body">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12 text-center">
							<div class="social m-b-0">' . $buttons . '</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		';
	}

	public function settingsDocker()
	{
		$type = ($this->docker) ? 'Docker' : 'Native';
		return '<li><div class="bg-info"><i class="mdi mdi-flag mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Install Type</span> ' . $type . '</li>';
	}

	public function settingsPathChecks()
	{
		$paths = $this->pathsWritable($this->paths);
		$items = '';
		$type = (array_search(false, $paths)) ? 'Not Writable' : 'Writable';
		$result = '<li class="mouse" onclick="toggleWritableFolders();"><div class="bg-info"><i class="mdi mdi-folder mdi-24px text-white"></i></div><span class="text-muted hidden-xs m-t-10" lang="en">Londerland Paths</span> ' . $type . '</li>';
		foreach ($paths as $k => $v) {
			$items .= '<li class="folders-writable hidden"><div class="bg-primary"><i class="mdi mdi-folder mdi-24px text-white"></i></div><a tabindex="0" type="button" class="btn btn-secondary btn-outline popover-info float-end clipboard" lang="en" data-bs-container="body" title="" data-bs-toggle="popover" data-bs-placement="left" data-content="' . $v['path'] . '" data-bs-title="File Path" data-clipboard-text="' . $v['path'] . '">' . $k . '</a> ' . (($v['writable']) ? 'Writable' : 'Not Writable') . '</li>';
		}
		return $result . $items;
	}

	public function pathsWritable($paths)
	{
		$results = array();
		foreach ($paths as $k => $v) {
			$results[$k] = [
				'writable' => is_writable($v),
				'path' => $v
			];
		}
		return $results;
	}

	public function clearTautulliTokens()
	{
		foreach (array_keys($_COOKIE) as $k => $v) {
			if (strpos($v, 'tautulli') !== false) {
				$this->coookie('delete', $v);
			}
		}
	}

	public function clearKomgaToken()
	{
		if (isset($_COOKIE['komga_token'])) {
			try {
				$url = $this->qualifyURL($this->config['komgaURL']);
				$options = $this->requestOptions($url, 60000, true, false);
				$response = \WpOrg\Requests\Requests::post($url . '/api/v1/users/logout', ['X-Auth-Token' => $_COOKIE['komga_token']], $options);
				if ($response->success) {
					$this->setLoggerChannel('Komga')->info('Logged User out');
				} else {
					$this->setLoggerChannel('Komga')->warning('Unable to Logged User out');
				}
			} catch (\WpOrg\Requests\Exception $e) {
				$this->setLoggerChannel('Komga')->error($e);
			}
			$this->coookie('delete', 'komga_token');
		}
	}

	public function analyzeIP($ip)
	{
		if (strpos($ip, '/') !== false) {
			$explodeIP = explode('/', $ip);
			$prefix = $explodeIP[1];
			$start_ip = $explodeIP[0];
			$ip_count = 1 << (32 - $prefix);
			$start_ip_long = ip2long($start_ip);
			$last_ip_long = ip2long($start_ip) + $ip_count - 1;
		} elseif (substr_count($ip, '.') == 3) {
			$start_ip_long = ip2long($ip);
			$last_ip_long = ip2long($ip);
		}
		return (isset($start_ip_long) && isset($last_ip_long)) ? array('from' => $start_ip_long, 'to' => $last_ip_long) : false;
	}

	public function authProxyRangeCheck($from, $to)
	{
		$approved = false;
		$userIP = ip2long($_SERVER['REMOTE_ADDR']);
		$low = $from;
		$high = $to;
		if ($userIP <= $high && $low <= $userIP) {
			$approved = true;
		}
		$this->logger->debug('authProxy range check', ['server_ip' => ['long' => $userIP, 'short' => long2ip($userIP)], 'range_from' => ['long' => $from, 'short' => long2ip($from)], 'range_to' => ['long' => $to, 'short' => long2ip($to)], 'approved' => $approved]);
		return $approved;
	}

	public function userDefinedIdReplacementLink($link, $variables)
	{
		if (!isset($link) || $link == '') {
			return null;
		}
		return strtr($link, $variables);
	}

	public function requestOptions($url, $timeout = null, $override = false, $customCertificate = false, $extras = null)
	{
		$options = [];
		if (is_numeric($timeout)) {
			if ($timeout >= 1000) {
				$timeout = $timeout / 1000;
			}
			$options = array_merge($options, array('timeout' => $timeout));
		}
		if ($this->localURL($url, $override)) {
			$options = array_merge($options, array('verify' => false, 'verifyname' => false));
		}
		if ($extras) {
			if (gettype($extras) == 'array') {
				$options = array_merge($options, $extras);
			}
		}
		return array_merge($options, array('useragent' => 'londerland/' . $this->version, 'connect_timeout' => 5));
	}

	public function showHTML(string $title = 'Londerland Alert', string $notice = '', bool $autoClose = false)
	{
		$close = $autoClose ? 'onLoad="setTimeout(\'closemyself()\',3000);"' : '';
		$closeMessage = $autoClose ? '<p><sup>(This window will close automatically)</sup></p>' : '';
		return
			'<!DOCTYPE html>
			<html lang="en">
			<head>
				<style>' . file_get_contents($this->root . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'mvp.css') . '</style>
				<meta charset="utf-8">
				<meta name="description" content="' . $title . '">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>' . $title . '</title>
			</head>
			<script language=javascript>
					function closemyself() {
						window.opener=self;
						window.close();
					}
			</script>
			<body ' . $close . '>
				<main>
					<section>
						<div>
							<h3>' . $title . '</h3>
							<p>' . $notice . '</p>
							' . $closeMessage . '
						</div>
					</section>
				</main>
			</body>
			</html>';
	}

	public function buildSettingsMenus($menuItems, $menuName)
	{
		$selectMenuItems = '';
		$unorderedListMenuItems = '';
		$menuNameLower = strtolower(str_replace(' ', '-', $menuName));
		foreach ($menuItems as $menuItem) {
			$anchorShort = str_replace('-anchor', '', $menuItem['anchor']);
			$active = ($menuItem['active']) ? 'active' : '';
			$apiPage = ($menuItem['api']) ? 'loadSettingsPage2(\'' . $menuItem['api'] . '\',\'#' . $anchorShort . '\',\'' . $menuItem['name'] . '\');' : '';
			$onClick = (isset($menuItem['onclick'])) ? $menuItem['onclick'] : '';
			$selectMenuItems .= '<option value="#' . $menuItem['anchor'] . '" lang="en">' . $menuItem['name'] . '</option>';
			$unorderedListMenuItems .= '
				<li onclick="changeSettingsMenu(\'Settings::' . $menuName . '::' . $menuItem['name'] . '\'); ' . $apiPage . $onClick . '" role="presentation" class="' . $active . '">
					<a id="' . $menuItem['anchor'] . '" href="#' . $anchorShort . '" aria-controls="home" role="tab" data-bs-toggle="tab" aria-expanded="true">
						<span lang="en">' . $menuItem['name'] . '</span>
					</a>
			</li>';
		}
		$selectMenu = '<select class="form-control settings-dropdown-box ' . $menuNameLower . '-menu w-100 visible-xs">' . $selectMenuItems . '</select>';
		$unorderedListMenu = '<ul class="nav customtab2 nav-tabs nav-non-mobile hidden-xs" data-dropdown="' . $menuNameLower . '-menu" role="tablist">' . $unorderedListMenuItems . '</ul>';
		return $selectMenu . $unorderedListMenu;
	}

	public function isJSON($string)
	{
		return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE);
	}

	public function isXML($string)
	{
		libxml_use_internal_errors(true);
		return (bool)simplexml_load_string($string);
	}

	public function testAndFormatString($string)
	{
		if ($this->isJSON($string)) {
			return ['type' => 'json', 'data' => json_decode($string, true)];
		} elseif ($this->isXML($string)) {
			libxml_use_internal_errors(true);
			return ['type' => 'xml', 'data' => simplexml_load_string($string)];
		} else {
			return ['type' => 'string', 'data' => $string];
		}
	}
}