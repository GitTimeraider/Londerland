<?php

trait OptionsFunction
{
	public function settingsOption($type, $name = null, $extras = null)
	{
		$type = strtolower(str_replace('-', '', $type));
		$setting = [
			'name' => $name,
			'value' => $this->config[$name] ?? ''
		];
		switch ($type) {
			case 'orguser':
				$this->setUserOptionsVariable();
				$settingMerge = [
					'type' => 'select',
					'label' => 'Londerland User',
					'options' => $this->userOptions
				];
				break;
			case 'enable':
				$settingMerge = [
					'type' => 'switch',
					'label' => 'Enable',
				];
				break;
			case 'auth':
				$this->setGroupOptionsVariable();
				$settingMerge = [
					'type' => 'select',
					'label' => 'Minimum Authentication',
					'options' => $this->groupOptions
				];
				break;
			case 'refresh':
				$settingMerge = [
					'type' => 'select',
					'label' => 'Refresh Seconds',
					'options' => $this->timeOptions()
				];
				break;
			case 'test':
				$settingMerge = [
					'type' => 'button',
					'label' => 'Test Connection',
					'icon' => 'fa fa-flask',
					'class' => 'float-end',
					'text' => 'Test Connection',
					'attr' => 'onclick="testAPIConnection(\'' . $name . '\')"',
					'help' => 'Remember! Please save before using the test button!'
				];
				break;
			case 'url':
				$settingMerge = [
					'type' => 'input',
					'label' => 'URL',
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port'
				];
				break;
			case 'multipleurl':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-select-' . $this->random_ascii_string(6),
					'label' => 'Multiple URL\'s',
					'help' => 'Please make sure to use local IP address and port - You also may use local dns name too.',
					'placeholder' => 'http(s)://hostname:port',
					'options' => $this->makeOptionsFromValues($this->config[$name]),
					'settings' => '{tags: true, selectOnClose: true, closeOnSelect: true, allowClear: true}',
				];
				break;
			case 'multiple':
				$settingMerge = [
					'type' => 'select2',
					'class' => 'select2-multiple',
					'id' => $name . '-select-' . $this->random_ascii_string(6),
					'label' => 'Multiple Values\'s',
					'options' => $this->makeOptionsFromValues($this->config[$name]),
					'settings' => '{tags: true, selectOnClose: true, closeOnSelect: true, allowClear: true}',
				];
				break;
			case 'cron':
				$settingMerge = [
					'type' => 'cron',
					'label' => 'Cron Schedule',
					'help' => 'You may use either Cron format or - @hourly, @daily, @monthly',
					'placeholder' => '* * * * *'
				];
				break;
			case 'folder':
				$settingMerge = [
					'type' => 'folder',
					'label' => 'Save Path',
					'help' => 'Folder path',
					'placeholder' => '/path/to/folder'
				];
				break;
			case 'cronfile':
				$path = $this->root . DIRECTORY_SEPARATOR . 'cron.php';
				$server = $this->serverIP();
				$installInstruction = ($this->docker) ?
					'<p lang="en">No action needed.  The Londerland Docker image runs scheduled jobs by itself</p>' :
					'<p lang="en">Setup a Cron job so it\'s call will originate from either the server\'s IP address or a local IP address.  Please use the following information to set up the Cron Job correctly.</p>
					<h5>Cron Information</h5>
					<ul class="list-icons">
						<li><i class="fa fa-caret-right text-info"></i> <b lang="en">Schedule</b> <small>* * * * *</small></li>
						<li><i class="fa fa-caret-right text-info"></i> <b lang="en">File Path</b> <small>' . $path . '</small></li>
					</ul>
					<h5>Command Examples</h5>
					<ul class="list-icons">
						<li><i class="ti-angle-right"></i> * * * * * /path/to/php ' . $path . '</li>
						<li><i class="ti-angle-right"></i> * * * * * curl -XGET -sL  "http://' . $server . '/cron.php"</li>
					</ul>
					';
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="row">
							<div class="col-xl-12">
								<div class="card card-info">
									<div class="card-header">
										<span lang="en">Londerland Enable Cron Instructions</span>
									</div>
									<div class="card-wrapper collapse show" aria-expanded="true">
										<div class="card-body">
											<h3 lang="en">Instructions for your install type</h3>
											<span>' . $installInstruction . '</span>
											<button type="button" onclick="checkCronFile();" class="btn btn-outline btn-info btn-lg w-100" lang="en">Check Cron Status</button>
											<div class="m-t-15 hidden cron-results-container">
												<div class="card card-body">
													<pre class="cron-results"></pre>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				];
				break;
			case 'username':
				$settingMerge = [
					'type' => 'input',
					'label' => 'Username',
				];
				break;
			case 'password':
				$settingMerge = [
					'type' => 'password',
					'label' => 'Password',
				];
				break;
			case 'passwordalt':
				$settingMerge = [
					'type' => 'password-alt',
					'label' => 'Password',
				];
				break;
			case 'passwordaltcopy':
				$settingMerge = [
					'type' => 'password-alt-copy',
					'label' => 'Password',
				];
				break;
			case 'apikey':
			case 'token':
				$settingMerge = [
					'type' => 'password-alt',
					'label' => 'API Key/Token',
				];
				break;
			case 'notice':
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => '',
					'html' => '
						<div class="row">
							<div class="col-xl-12">
								<div class="card card-' . ($extras['notice'] ?? 'info') . '">
									<div class="card-header">
										<span lang="en">' . ($extras['title'] ?? 'Attention') . '</span>
									</div>
									<div class="card-wrapper collapse show" aria-expanded="true">
										<div class="card-body">
											<span lang="en">' . ($extras['body'] ?? '') . '</span>
											<span>' . ($extras['bodyHTML'] ?? '') . '</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						'
				];
				break;
			case 'blank':
				$settingMerge = [
					'type' => 'blank',
					'label' => '',
				];
				break;
			// HTML ITEMS
			// precodeeditor possibly not needed anymore
			case 'codeeditor':
				$mode = strtolower($extras['mode'] ?? 'css');
				switch ($mode) {
					case 'html':
					case 'javascript':
						$mode = 'ace/mode/' . $mode;
						break;
					case 'js':
						$mode = 'ace/mode/javascript';
						break;
					default:
						$mode = 'ace/mode/css';
						break;
				}
				$settingMerge = [
					'type' => 'html',
					'override' => 12,
					'label' => 'Custom Code',
					'html' => '
					<textarea data-changed="false" class="form-control hidden ' . $name . 'Textarea" name="' . $name . '" data-type="textbox" autocomplete="new-password">' . $this->config[$name] . '</textarea>
					<div id="' . $name . 'Editor" style="height:300px">' . htmlentities($this->config[$name]) . '</div>
					<script>
						londerlandLoadLibrary("ace").then(function () {
						' . str_replace('-', '', $name) . ' = ace.edit("' . $name . 'Editor");
						' . str_replace('-', '', $name) . '.session.setMode("' . $mode . '");
						' . str_replace('-', '', $name) . '.setTheme("ace/theme/idle_fingers");
						' . str_replace('-', '', $name) . '.setShowPrintMargin(false);
						' . str_replace('-', '', $name) . '.session.on("change", function(delta) { 
							$(".' . $name . 'Textarea").val(' . str_replace('-', '', $name) . '.getValue());
							$(".' . $name . 'Textarea").trigger("change");
                        });
						});
					</script>
					'
				];
				break;
			// CALENDAR ITEMS
			case 'color':
				$settingMerge = [
					'type' => 'input',
					'label' => 'Color',
					'class' => 'pick-a-color-custom-options',
					'attr' => 'data-original="' . $this->config[$name] . '"'
				];
				break;
			default:
				$settingMerge = [
					'type' => strtolower($type),
					'label' => ''
				];
				break;
		}
		$setting = array_merge($settingMerge, $setting);
		if ($extras) {
			if (gettype($extras) == 'array') {
				$setting = array_merge($setting, $extras);
			}
		}
		return $setting;
	}

	public function makeOptionsFromValues($values = null, $appendBlank = null, $blankLabel = null)
	{
		if ($appendBlank === true) {
			$formattedValues[] = [
				'name' => (!empty($blankLabel)) ? $blankLabel : 'Select option...',
				'value' => ''
			];
		} else {
			$formattedValues = [];
		}
		if (strpos($values, ',') !== false) {
			$explode = explode(',', $values);
			foreach ($explode as $item) {
				$formattedValues[] = [
					'name' => $item,
					'value' => $item
				];
			}
		} elseif ($values == '') {
			$formattedValues = '';
		} else {
			$formattedValues[] = [
				'name' => $values,
				'value' => $values
			];
		}
		return $formattedValues;
	}

	public function logLevels()
	{
		return [
			[
				'name' => 'Debug',
				'value' => 'DEBUG'
			],
			[
				'name' => 'Info',
				'value' => 'INFO'
			],
			[
				'name' => 'Notice',
				'value' => 'NOTICE'
			],
			[
				'name' => 'Warning',
				'value' => 'WARNING'
			],
			[
				'name' => 'Error',
				'value' => 'ERROR'
			],
			[
				'name' => 'Critical',
				'value' => 'CRITICAL'
			],
			[
				'name' => 'Alert',
				'value' => 'ALERT'
			],
			[
				'name' => 'Emergency',
				'value' => 'EMERGENCY'
			]
		];
	}

	public function sandboxOptions()
	{
		return [
			[
				'name' => 'Allow Presentation',
				'value' => 'allow-presentation'
			],
			[
				'name' => 'Allow Forms',
				'value' => 'allow-forms'
			],
			[
				'name' => 'Allow Same Origin',
				'value' => 'allow-same-origin'
			],
			[
				'name' => 'Allow Orientation Lock',
				'value' => 'allow-orientation-lock'
			],
			[
				'name' => 'Allow Pointer Lock',
				'value' => 'allow-pointer-lock'
			],
			[
				'name' => 'Allow Scripts',
				'value' => 'allow-scripts'
			],
			[
				'name' => 'Allow Popups',
				'value' => 'allow-popups'
			],
			[
				'name' => 'Allow Popups To Escape Sandbox',
				'value' => 'allow-popups-to-escape-sandbox'
			],
			[
				'name' => 'Allow Modals',
				'value' => 'allow-modals'
			],
			[
				'name' => 'Allow Top Navigation',
				'value' => 'allow-top-navigation'
			],
			[
				'name' => 'Allow Top Navigation By User Activation',
				'value' => 'allow-top-navigation-by-user-activation'
			],
			[
				'name' => 'Allow Downloads',
				'value' => 'allow-downloads'
			],
		];
	}

	public function iframeAllowOptions()
	{
		return [
			[
				'name' => 'Allow Clipboard Read',
				'value' => 'clipboard-read'
			],
			[
				'name' => 'Allow Clipboard Write',
				'value' => 'clipboard-write'
			],
			[
				'name' => 'Allow Camera',
				'value' => 'camera'
			],
			[
				'name' => 'Allow Microphone',
				'value' => 'microphone'
			],
			[
				'name' => 'Allow Speaker Selection',
				'value' => 'speaker-selection'
			],
			[
				'name' => 'Allow Encrypted Media',
				'value' => 'encrypted-media'
			],
			[
				'name' => 'Allow Web Share',
				'value' => 'web-share'
			],
			[
				'name' => 'Allow Capture the Screen',
				'value' => 'display-capture'
			],
			[
				'name' => 'Allow Screen Wake Lock',
				'value' => 'screen-wake-lock'
			],
			[
				'name' => 'Allow Geolocation',
				'value' => 'geolocation'
			],
			[
				'name' => 'Allow Autoplay Media',
				'value' => 'autoplay'
			],
			[
				'name' => 'Allow USB',
				'value' => 'usb'
			],
			[
				'name' => 'Allow MIDI',
				'value' => 'midi'
			],
			[
				'name' => 'Allow Fullscreen',
				'value' => 'fullscreen'
			],
			[
				'name' => 'Allow Payment',
				'value' => 'payment'
			],
			[
				'name' => 'Allow Picture-in-Picture',
				'value' => 'picture-in-picture'
			],
			[
				'name' => 'Allow Gamepad',
				'value' => 'gamepad'
			],
			[
				'name' => 'Allow WebXR Spatial Tracking (VR)',
				'value' => 'xr-spatial-tracking'
			],
			[
				'name' => 'Allow Accelerometer Sensor',
				'value' => 'accelerometer'
			],
			[
				'name' => 'Allow Gyroscope Sensor',
				'value' => 'gyroscope'
			],
			[
				'name' => 'Allow Magnetometer Sensor',
				'value' => 'magnetometer'
			],
			[
				'name' => 'Allow Ambient Light Sensor',
				'value' => 'ambient-light-sensor'
			],
			[
				'name' => 'Allow Battery Status',
				'value' => 'battery'
			],
			[
				'name' => 'Allow Sync XMLHttpRequest',
				'value' => 'sync-xhr'
			],
		];
	}

	public function notificationTypesOptions()
	{
		return array(
			array(
				'name' => 'Bootstrap',
				'value' => 'bootstrap'
			),
			array(
				'name' => 'Alertify',
				'value' => 'alertify'
			),
		);
	}

	public function notificationPositionsOptions()
	{
		return array(
			array(
				'name' => 'Bottom Right',
				'value' => 'br'
			),
			array(
				'name' => 'Bottom Left',
				'value' => 'bl'
			),
			array(
				'name' => 'Bottom Center',
				'value' => 'bc'
			),
			array(
				'name' => 'Top Right',
				'value' => 'tr'
			),
			array(
				'name' => 'Top Left',
				'value' => 'tl'
			),
			array(
				'name' => 'Top Center',
				'value' => 'tc'
			),
			array(
				'name' => 'Center',
				'value' => 'c'
			),
		);
	}

	public function timeOptions()
	{
		return array(
			array(
				'name' => '2.5',
				'value' => '2500'
			),
			array(
				'name' => '5',
				'value' => '5000'
			),
			array(
				'name' => '10',
				'value' => '10000'
			),
			array(
				'name' => '15',
				'value' => '15000'
			),
			array(
				'name' => '30',
				'value' => '30000'
			),
			array(
				'name' => '60 [1 Minute]',
				'value' => '60000'
			),
			array(
				'name' => '300 [5 Minutes]',
				'value' => '300000'
			),
			array(
				'name' => '600 [10 Minutes]',
				'value' => '600000'
			),
			array(
				'name' => '900 [15 Minutes]',
				'value' => '900000'
			),
			array(
				'name' => '1800 [30 Minutes]',
				'value' => '1800000'
			),
			array(
				'name' => '3600 [1 Hour]',
				'value' => '3600000'
			),
		);
	}

}
