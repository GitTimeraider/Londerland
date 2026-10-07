<?php

use Cron\CronExpression;

/**
 * Runs Organizr's cron jobs (cron.php and plugin advancedCron.php files).
 * Keeps the job API of the previously used peppeocchi/php-cron-scheduler for closures.
 */
class OrganizrScheduler
{
	private array $jobs = [];
	private array $executedJobs = [];
	private array $failedJobs = [];
	private array $verboseOutput = [];

	public function call(callable $fn, $args = [], $id = null): OrganizrScheduledJob
	{
		$job = new OrganizrScheduledJob($fn, (array)$args, $id);
		$this->jobs[] = $job;
		return $job;
	}

	public function getQueuedJobs(): array
	{
		return $this->jobs;
	}

	public function run(?DateTime $runTime = null): array
	{
		$runTime = $runTime ?? new DateTime('now');
		foreach ($this->jobs as $job) {
			if (!$job->isDue($runTime)) {
				continue;
			}
			try {
				if ($job->run()) {
					$this->executedJobs[] = $job;
					$this->addVerboseOutput('Executing ' . $job->getId());
				}
			} catch (\Throwable $e) {
				$this->failedJobs[] = ['job' => $job->getId(), 'error' => $e->getMessage()];
				$this->addVerboseOutput($e->getMessage() . ': ' . $job->getId());
			}
		}
		return $this->executedJobs;
	}

	public function resetRun(): static
	{
		$this->executedJobs = [];
		$this->failedJobs = [];
		$this->verboseOutput = [];
		return $this;
	}

	public function getExecutedJobs(): array
	{
		return $this->executedJobs;
	}

	public function getFailedJobs(): array
	{
		return $this->failedJobs;
	}

	public function getVerboseOutput($type = 'text')
	{
		return match ($type) {
			'html' => implode('<br>', $this->verboseOutput),
			'array' => $this->verboseOutput,
			default => implode("\n", $this->verboseOutput),
		};
	}

	public function clearJobs(): static
	{
		$this->jobs = [];
		return $this;
	}

	// Shell commands (raw/php) are not supported; log instead of breaking the whole cron run
	public function __call($name, $arguments)
	{
		(new Organizr())->log('Cron')->warning('Unsupported scheduler method called', ['method' => $name]);
		return $this->call(fn() => null)->when(fn() => false);
	}

	private function addVerboseOutput(string $line): void
	{
		$this->verboseOutput[] = '[' . (new DateTime('now'))->format('c') . '] ' . $line;
	}
}

class OrganizrScheduledJob
{
	private $fn;
	private array $args;
	private string $id;
	private ?CronExpression $expression = null;
	private ?string $year = null;
	private bool $truthTest = true;
	private $before = null;
	private $after = null;
	private ?string $lockFile = null;
	private array $outputFiles = [];
	private bool $appendOutput = false;

	public function __construct(callable $fn, array $args = [], $id = null)
	{
		$this->fn = $fn;
		$this->args = $args;
		$this->id = $id ?? 'Closure-' . spl_object_id($this);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function isDue(?DateTime $date = null): bool
	{
		$date = $date ?? new DateTime('now');
		if ($this->year !== null && $this->year !== $date->format('Y')) {
			return false;
		}
		return ($this->expression ?? new CronExpression('* * * * *'))->isDue($date);
	}

	public function run(): bool
	{
		if (!$this->truthTest) {
			return false;
		}
		if ($this->lockFile && file_exists($this->lockFile)) {
			return false;
		}
		if ($this->lockFile) {
			file_put_contents($this->lockFile, $this->id);
		}
		try {
			if ($this->before) {
				call_user_func($this->before);
			}
			ob_start();
			try {
				$result = call_user_func_array($this->fn, $this->args);
			} finally {
				$output = ob_get_clean();
			}
			$output .= is_string($result) ? $result : '';
			foreach ($this->outputFiles as $file) {
				file_put_contents($file, $output, $this->appendOutput ? FILE_APPEND : 0);
			}
			if ($this->after) {
				call_user_func($this->after, $output);
			}
		} finally {
			if ($this->lockFile && file_exists($this->lockFile)) {
				unlink($this->lockFile);
			}
		}
		return true;
	}

	public function at($expression): static
	{
		$this->expression = new CronExpression($expression);
		return $this;
	}

	public function date($date): static
	{
		$date = $date instanceof DateTime ? $date : new DateTime($date);
		$this->year = $date->format('Y');
		return $this->at($date->format('i H d m') . ' *');
	}

	public function everyMinute($minute = null): static
	{
		return $this->at(($minute === null ? '*' : '*/' . $this->range($minute, 0, 59)) . ' * * * *');
	}

	public function hourly($minute = 0): static
	{
		return $this->at($this->range($minute, 0, 59) . ' * * * *');
	}

	public function daily($hour = 0, $minute = 0): static
	{
		[$hour, $minute] = $this->splitTime($hour, $minute);
		return $this->at($this->range($minute, 0, 59) . ' ' . $this->range($hour, 0, 23) . ' * * *');
	}

	public function weekly($weekday = 0, $hour = 0, $minute = 0): static
	{
		[$hour, $minute] = $this->splitTime($hour, $minute);
		return $this->at($this->range($minute, 0, 59) . ' ' . $this->range($hour, 0, 23) . ' * * ' . $this->range($weekday, 0, 6));
	}

	public function monthly($month = '*', $day = 1, $hour = 0, $minute = 0): static
	{
		[$hour, $minute] = $this->splitTime($hour, $minute);
		return $this->at($this->range($minute, 0, 59) . ' ' . $this->range($hour, 0, 23) . ' ' . $this->range($day, 1, 31) . ' ' . $this->range($month, 1, 12) . ' *');
	}

	public function sunday($hour = 0, $minute = 0): static { return $this->weekly(0, $hour, $minute); }
	public function monday($hour = 0, $minute = 0): static { return $this->weekly(1, $hour, $minute); }
	public function tuesday($hour = 0, $minute = 0): static { return $this->weekly(2, $hour, $minute); }
	public function wednesday($hour = 0, $minute = 0): static { return $this->weekly(3, $hour, $minute); }
	public function thursday($hour = 0, $minute = 0): static { return $this->weekly(4, $hour, $minute); }
	public function friday($hour = 0, $minute = 0): static { return $this->weekly(5, $hour, $minute); }
	public function saturday($hour = 0, $minute = 0): static { return $this->weekly(6, $hour, $minute); }
	public function january($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(1, $day, $hour, $minute); }
	public function february($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(2, $day, $hour, $minute); }
	public function march($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(3, $day, $hour, $minute); }
	public function april($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(4, $day, $hour, $minute); }
	public function may($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(5, $day, $hour, $minute); }
	public function june($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(6, $day, $hour, $minute); }
	public function july($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(7, $day, $hour, $minute); }
	public function august($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(8, $day, $hour, $minute); }
	public function september($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(9, $day, $hour, $minute); }
	public function october($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(10, $day, $hour, $minute); }
	public function november($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(11, $day, $hour, $minute); }
	public function december($day = 1, $hour = 0, $minute = 0): static { return $this->monthly(12, $day, $hour, $minute); }

	public function when(callable $fn): static
	{
		$this->truthTest = $fn() === true;
		return $this;
	}

	public function before(callable $fn): static
	{
		$this->before = $fn;
		return $this;
	}

	public function then(callable $fn, $runInBackground = false): static
	{
		$this->after = $fn;
		return $this;
	}

	// Closures always run in the foreground
	public function inForeground(): static
	{
		return $this;
	}

	// Skip the job while a previous run still holds its lock file
	public function onlyOne($tempDir = null, ?callable $whenOverlapping = null): static
	{
		$this->lockFile = rtrim($tempDir ?? sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'organizr-cron-' . md5($this->id) . '.lock';
		return $this;
	}

	public function output($filename, $append = false): static
	{
		$this->outputFiles = (array)$filename;
		$this->appendOutput = (bool)$append;
		return $this;
	}

	// Features such as e-mailing job output are not supported; log instead of breaking the whole cron run
	public function __call($name, $arguments)
	{
		(new Organizr())->log('Cron')->warning('Unsupported cron job method called', ['method' => $name, 'job' => $this->id]);
		return $this;
	}

	private function splitTime($hour, $minute): array
	{
		if (is_string($hour) && str_contains($hour, ':')) {
			[$hour, $minute] = array_pad(explode(':', $hour), 2, '0');
		}
		return [$hour, $minute];
	}

	private function range($value, int $min, int $max): string
	{
		if ($value === null || $value === '*') {
			return '*';
		}
		if (!is_numeric($value) || $value < $min || $value > $max) {
			throw new InvalidArgumentException("Invalid value: it should be '*' or between {$min} and {$max}.");
		}
		return (string)(int)$value;
	}
}
