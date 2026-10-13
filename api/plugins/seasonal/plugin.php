<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Seasonal Themes'] = array( // Plugin Name
	'name' => 'Seasonal Themes', // Plugin Name
	'author' => 'Londerland', // Who wrote the plugin
	'category' => 'Fun', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'SEASONAL', // html element id prefix
	'configPrefix' => 'SEASONAL', // config file prefix for array items without the hyphen
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'api/plugins/seasonal/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/seasonal/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

/**
 * Seasonal decorations on special days: a small scene in the bottom left corner (a snowman with a bit of snow,
 * a glowing pumpkin, ...), sometimes a figure along the bottom edge, a decoration on the logo and a greeting.
 * Nothing covers the middle of the screen. Everything happens in the browser, which knows the local date;
 * the server only stores the settings.
 */
class SeasonalThemes extends Londerland
{
	// key => [name, what it shows, fixed dates (false = calculated), how the date is calculated]
	public const SEASONS = [
		'newyear' => ['New Year', 'A champagne bottle with a bit of confetti', true, ''],
		'valentine' => ['Valentine\'s Day', 'A love letter with rising hearts', true, ''],
		'easter' => ['Easter', 'A hopping bunny and a chick waddling along the bottom', false, 'Good Friday to Easter Monday, calculated every year.'],
		'aprilfools' => ['April Fools\' Day', 'A bug crawling along the bottom and an upside-down logo', true, ''],
		'kingsday' => ['King\'s Day (NL)', 'A crown with orange confetti', true, ''],
		'sysadminday' => ['SysAdmin Day', 'A steaming cup of coffee', false, 'The last Friday of July, calculated every year.'],
		'programmersday' => ['Programmers\' Day', 'A computer with falling ones and zeros', false, 'Day 256 of the year (13 September, or 12 September in a leap year).'],
		'halloween' => ['Halloween', 'A glowing pumpkin and a ghost floating along the bottom', true, ''],
		'christmas' => ['Christmas', 'A snowman with a bit of snow and a Santa hat on the logo', true, ''],
	];

	public function _seasonalGetSettings()
	{
		$forceOptions = [['name' => 'Automatic (by date)', 'value' => 'auto']];
		$seasons = [];
		foreach (self::SEASONS as $key => [$name, $effect, $fixedDates, $calculated]) {
			$forceOptions[] = ['name' => 'Always: ' . $name, 'value' => $key];
			$seasons[] = array(
				'type' => 'switch',
				'name' => 'SEASONAL-' . $key . '-include',
				'label' => $name,
				'help' => $effect . '.',
				'value' => $this->config['SEASONAL-' . $key . '-include']
			);
			if ($fixedDates) {
				$seasons[] = array(
					'type' => 'input',
					'name' => 'SEASONAL-' . $key . '-dates-include',
					'label' => $name . ' dates',
					'value' => $this->config['SEASONAL-' . $key . '-dates-include'],
					'placeholder' => 'MM-DD - MM-DD',
					'help' => 'One day (04-01) or a period (12-01 - 12-26). A period may run into the new year (12-31 - 01-01).'
				);
			} else {
				$seasons[] = array(
					'type' => 'html',
					'label' => $name . ' dates',
					'html' => '<p class="m-t-10" lang="en">' . $calculated . '</p>'
				);
			}
		}
		return array(
			'custom' => '
				<div class="row">
					<div class="col-xl-12">
						<div class="card card-info">
							<div class="card-header"><span lang="en">How it works</span></div>
							<div class="card-wrapper collapse show" aria-expanded="true">
								<div class="card-body">
									<ul class="list-icons">
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">On the days of a season, Londerland shows a small scene in the bottom left corner (for example a snowman with a bit of snow), sometimes a figure that moves along the bottom edge, a decoration on the logo and a greeting once a day. Nothing covers the middle of the screen.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">The date is the date on the viewer\'s own device. When two seasons overlap, the shorter (special day) one wins.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Nothing moves for people who asked their device for reduced motion, and everything pauses while the browser tab is in the background.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Use Show season to try a season out today. Set it back to Automatic afterwards.</span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>',
			'Options' => array(
				array(
					'type' => 'select',
					'name' => 'SEASONAL-audience-include',
					'label' => 'Show to',
					'value' => $this->config['SEASONAL-audience-include'],
					'options' => array(
						['name' => 'Everyone (users and guests)', 'value' => 'both'],
						['name' => 'Logged in users only', 'value' => 'users'],
						['name' => 'Guests only', 'value' => 'guests'],
					)
				),
				array(
					'type' => 'select',
					'name' => 'SEASONAL-force-include',
					'label' => 'Show season',
					'value' => $this->config['SEASONAL-force-include'],
					'options' => $forceOptions,
					'help' => 'Automatic follows the dates below. Choosing a season shows it every day, for everyone in Show to.'
				),
				array(
					'type' => 'switch',
					'name' => 'SEASONAL-effects-include',
					'label' => 'Corner scene',
					'help' => 'The figure in the bottom left corner with its bit of snow, confetti or steam, and the figure that moves along the bottom edge (Easter, April Fools\' Day, Halloween).',
					'value' => $this->config['SEASONAL-effects-include']
				),
				array(
					'type' => 'select',
					'name' => 'SEASONAL-intensity-include',
					'label' => 'Amount of snow, confetti and the like',
					'value' => $this->config['SEASONAL-intensity-include'],
					'options' => array(
						['name' => 'A little', 'value' => 'low'],
						['name' => 'Normal', 'value' => 'medium'],
						['name' => 'A lot', 'value' => 'high'],
					)
				),
				array(
					'type' => 'switch',
					'name' => 'SEASONAL-overTabs-include',
					'label' => 'Also on top of website tabs',
					'help' => 'Off: the corner scene only shows on Londerland\'s own pages (homepage, settings), not on top of the apps you open.',
					'value' => $this->config['SEASONAL-overTabs-include']
				),
				array(
					'type' => 'switch',
					'name' => 'SEASONAL-decorations-include',
					'label' => 'Decoration next to the logo',
					'value' => $this->config['SEASONAL-decorations-include']
				),
				array(
					'type' => 'switch',
					'name' => 'SEASONAL-greeting-include',
					'label' => 'Greeting message (once a day)',
					'value' => $this->config['SEASONAL-greeting-include']
				),
				array(
					'type' => 'switch',
					'name' => 'SEASONAL-userToggle-include',
					'label' => 'Users may switch effects off',
					'help' => 'Adds Seasonal effects on/off to the user menu (also for guests) while a season is showing. The choice is remembered per browser.',
					'value' => $this->config['SEASONAL-userToggle-include']
				),
			),
			'Seasons' => $seasons
		);
	}
}
