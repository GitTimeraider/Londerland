<?php
require_once 'api/functions.php';
$Londerland = new Londerland();
if ($Londerland->isLocalOrServer() && $Londerland->hasDB()) {
	// Set user as Londerland API
	$_GET['apikey'] = $Londerland->config['londerlandAPI'];
	// Create a new scheduler
	$scheduler = new LonderlandScheduler();
	// Clear any pre-existing jobs if any
	$scheduler->clearJobs();
	$Londerland->log('Cron')->debug('Cron process starting');
	// Auto-backup Cron
	if ($Londerland->config['autoBackupCronEnabled'] && $Londerland->config['autoBackupCronSchedule']) {
		try {
			$schedule = new Cron\CronExpression($Londerland->config['autoBackupCronSchedule']);
			$Londerland->log('Cron')->debug('Cron schedule has passed validation', ['schedule' => $Londerland->config['autoBackupCronSchedule']]);
			$scheduler->call(
				function () use ($Londerland) {
					$Londerland->log('Cron')->debug('Running cron job', ['function' => 'Auto-backup']);
					return $Londerland->backupLonderland();
				})
				->then(function ($output) use ($Londerland) {
					$Londerland->log('Cron')->debug('Completed cron job', [
						'output' => $output,
					]);
				})
				->at($Londerland->config['autoBackupCronSchedule']);
		} catch (InvalidArgumentException $e) {
			$Londerland->log('Cron')->warning('Cron schedule has failed validation', ['schedule' => $Londerland->config['autoBackupCronSchedule']]);
			$Londerland->log('Cron')->error($e);
		} catch (Exception $e) {
			$Londerland->log('Cron')->error($e);
		}
	}
	// End Auto-backup Cron
	// Add plugin cron
	$Londerland->log('Cron')->debug('Checking if any plugins have cron jobs');
	foreach ($GLOBALS['cron'] as $cronJob) {
		if (isset($cronJob['enabled']) && isset($cronJob['class']) && isset($cronJob['function']) && isset($cronJob['schedule'])) {
			$Londerland->log('Cron')->debug('Starting cron job for function: ' . $cronJob['function'], ['cronJob' => $cronJob]);
			if ($Londerland->config[$cronJob['enabled']]) {
				$Londerland->log('Cron')->debug('Checking if cron job class exists', ['cronJob' => $cronJob]);
				if (class_exists($cronJob['class'])) {
					$Londerland->log('Cron')->debug('Class exists', ['cronJob' => $cronJob]);
					$Londerland->log('Cron')->debug('Validating cron job schedule', ['schedule' => $cronJob['schedule']]);
					try {
						$schedule = new Cron\CronExpression($Londerland->config[$cronJob['schedule']]);
						$Londerland->log('Cron')->debug('Cron schedule has passed validation', ['schedule' => $Londerland->config[$cronJob['schedule']]]);
						$plugin = new $cronJob['class']();
						$function = $cronJob['function'];
						$Londerland->log('Cron')->debug('Checking if cron job method exists', ['cronJob' => $cronJob]);
						if (method_exists($plugin, $function)) {
							$Londerland->log('Cron')->debug('Method exists', ['cronJob' => $cronJob]);
							$scheduler->call(
								function ($plugin, $function) use ($Londerland) {
									$Londerland->log('Cron')->debug('Running cron job', ['function' => $function]);
									return $plugin->$function();
								}, [$plugin, $function])
								->then(function ($output) use ($Londerland) {
									$Londerland->log('Cron')->debug('Completed cron job', [
										'output' => $output,
									]);
								})
								->at($Londerland->config[$cronJob['schedule']]);
						} else {
							$Londerland->log('Cron')->warning('Method error', ['cronJob' => $cronJob['class']]);
						}
					} catch (InvalidArgumentException $e) {
						$Londerland->log('Cron')->warning('Cron schedule has failed validation', ['schedule' => $Londerland->config[$cronJob['schedule']]]);
						$Londerland->log('Cron')->error($e);
						break;
					} catch (Exception $e) {
						$Londerland->log('Cron')->error($e);
						break;
					}
				} else {
					$Londerland->log('Cron')->warning('Class error', ['cronJob' => $cronJob['class']]);
				}
			} else {
				$Londerland->log('Cron')->debug('Cron job is not enabled', ['cronJob' => $cronJob]);
			}
		} else {
			$Londerland->log('Cron')->warning('Cron job was setup incorrectly', ['cronJob' => $cronJob]);
		}
	}
	$Londerland->log('Cron')->debug('Finished processing plugin cron jobs');
	/*
	 * Include plugin advanced cron
	 */
	$Londerland->log('Cron')->debug('Checking if any Plugins have advanced cron jobs');
	try {
		$directoryIterator = new RecursiveDirectoryIterator($Londerland->root . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'plugins', FilesystemIterator::SKIP_DOTS);
		$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
		foreach ($iteratorIterator as $info) {
			if ($info->getFilename() == 'advancedCron.php') {
				require_once $info->getPathname();
			}
		}
	} catch (UnexpectedValueException $e) {
		$Londerland->log('Cron')->error($e);
	}
	/*
	 * Include custom plugin advanced cron
	 */
	try {
		if (file_exists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins')) {
			$folder = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins';
			$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
			$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
			foreach ($iteratorIterator as $info) {
				if ($info->getFilename() == 'advancedCron.php') {
					require_once $info->getPathname();
				}
			}
		}
	} catch (UnexpectedValueException $e) {
		$Londerland->log('Cron')->error($e);
	}
	$Londerland->log('Cron')->debug('Finished processing advanced plugin cron jobs');
	// Run cron jobs
	$scheduler->run();
	// Debug stuff
	//$Londerland->prettyPrint($scheduler->getVerboseOutput());
	//$Londerland->prettyPrint($scheduler->getFailedJobs());
	$Londerland->log('Cron')->debug('Cron process completion', ['verbose' => $scheduler->getVerboseOutput()]);
	if (!empty($scheduler->getFailedJobs())) {
		$Londerland->log('Cron')->warning('Cron jobs have failed', ['jobs' => $scheduler->getFailedJobs(), 'verbose' => $scheduler->getVerboseOutput()]);
	}
	// End Run and set file with time
	$Londerland->createCronFile();
} else {
	if ($Londerland->hasDB()) {
		$Londerland->log('Cron')->warning('Unauthorized user tried to access cron file');
		die($Londerland->showHTML('Unauthorized', 'Go-on.... Git!!!'));
	}
	die('Unauthorized');
}