<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Ramsey\Uuid\Uuid;

/**
 * Writes Organizr's JSON log lines (one object per line, read back by LogFunctions::readLog).
 * Exceptions may be passed as the message; they are logged as their class name with an "errors" block.
 */
class OrganizrLogger extends Logger
{
	private const ERRORS_KEY = '_organizr_errors';
	private string $username;

	public function __construct(string $channel, string $username, string $file, int $maxFiles, Level $level, ?SlackWebhookHandler $slackHandler = null)
	{
		$this->username = $username !== '' ? $username : Uuid::uuid4()->toString();
		$formatter = new OrganizrLogFormatter();
		$fileHandler = new RotatingFileHandler($file, $maxFiles, $level);
		$fileHandler->setFormatter($formatter);
		$handlers = [$fileHandler];
		$processors = [
			new IntrospectionProcessor($level, ['OrganizrLogger']),
			function (LogRecord $record): LogRecord {
				$record->extra['trace_id'] = $this->username;
				$record->extra['created_time'] = microtime(true);
				return $record;
			},
		];
		if ($slackHandler) {
			$slackHandler->setFormatter($formatter);
			$handlers[] = $slackHandler;
			$webProcessor = new WebProcessor();
			$webProcessor->addExtraField('server_ip_address', 'SERVER_ADDR');
			$webProcessor->addExtraField('user_agent', 'HTTP_USER_AGENT');
			$processors[] = $webProcessor;
		}
		parent::__construct($channel, $handlers, $processors);
	}

	public function getChannel(): string
	{
		return $this->getName();
	}

	public function getTraceId(): string
	{
		return $this->username;
	}

	public function debug($message, $context = []): void
	{
		$this->write(Level::Debug, $message, $context);
	}

	public function info($message, $context = []): void
	{
		$this->write(Level::Info, $message, $context);
	}

	public function notice($message, $context = []): void
	{
		$this->write(Level::Notice, $message, $context);
	}

	public function warning($message, $context = []): void
	{
		$this->write(Level::Warning, $message, $context);
	}

	public function error($message, $context = []): void
	{
		$this->write(Level::Error, $message, $context);
	}

	public function critical($message, $context = []): void
	{
		$this->write(Level::Critical, $message, $context);
	}

	public function alert($message, $context = []): void
	{
		$this->write(Level::Alert, $message, $context);
	}

	public function emergency($message, $context = []): void
	{
		$this->write(Level::Emergency, $message, $context);
	}

	private function write(Level $level, $message, $context): void
	{
		if (!is_array($context)) {
			$context = empty($context) ? [] : ['data' => $context];
		}
		if ($message instanceof \Throwable) {
			$context[self::ERRORS_KEY] = [
				'message' => $message->getMessage(),
				'code' => $message->getCode(),
				'file' => $message->getFile(),
				'line' => $message->getLine(),
				'trace' => $this->formatStackTrace($message->getTrace()),
			];
			$message = get_class($message);
		}
		$this->addRecord($level, (string)$message, $context);
	}

	private function formatStackTrace(array $traces): array
	{
		$formatted = [];
		foreach (array_values($traces) as $i => $trace) {
			$formatted[] = sprintf('#%s %s(%s): %s%s%s()', $i, $trace['file'] ?? '', $trace['line'] ?? '', $trace['class'] ?? '', $trace['type'] ?? '', $trace['function'] ?? '');
		}
		return $formatted;
	}

	public static function errorsKey(): string
	{
		return self::ERRORS_KEY;
	}
}

class OrganizrLogFormatter extends JsonFormatter
{
	public function format(LogRecord $record): string
	{
		$context = $record->context;
		$errors = $context[OrganizrLogger::errorsKey()] ?? null;
		unset($context[OrganizrLogger::errorsKey()]);
		$formatted = [
			'log_level' => $record->level->getName(),
			'message' => $record->message,
			'channel' => $record->channel,
			'username' => $record->extra['trace_id'] ?? '',
			'trace_id' => Uuid::uuid4()->toString(),
			'file' => $record->extra['file'] ?? null,
			'line' => $record->extra['line'] ?? null,
			'context' => $context,
			'remote_ip_address' => $this->remoteIpAddress(),
			'server_ip_address' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
			'datetime' => $record->datetime->format('Y-m-d H:i:s.u'),
			'timezone' => $record->datetime->getTimezone()->getName(),
			'process_time' => (microtime(true) - ($record->extra['created_time'] ?? microtime(true))) * 1000,
		];
		if ($errors !== null) {
			$formatted['errors'] = $errors;
		}
		return $this->toJson($this->normalize($formatted), true) . ($this->appendNewline ? "\n" : '');
	}

	private function remoteIpAddress(): string
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
		}
		return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	}
}
