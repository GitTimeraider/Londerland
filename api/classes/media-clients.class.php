<?php

/**
 * Minimal API clients for the Sonarr/Radarr/Lidarr, SickRage and CouchPotato homepage items.
 * Each method returns the raw JSON response body.
 */
abstract class OrganizrMediaClient
{
	protected string $url;
	protected string $apiKey;
	protected ?string $httpAuthUsername;
	protected ?string $httpAuthPassword;
	protected array $options;

	public function __construct($url, $apiKey, $httpAuthUsername = null, $httpAuthPassword = null, $options = [])
	{
		$this->url = rtrim((string)$url, '/\\');
		$this->apiKey = (string)$apiKey;
		$this->httpAuthUsername = $httpAuthUsername ?: null;
		$this->httpAuthPassword = $httpAuthPassword ?: null;
		$this->options = is_array($options) ? $options : [];
	}

	// GET the URL and return the body; throws on connection errors and HTTP error statuses
	protected function get(string $url, array $headers = []): string
	{
		$options = $this->options;
		if ($this->httpAuthUsername && $this->httpAuthPassword) {
			$options['auth'] = [$this->httpAuthUsername, $this->httpAuthPassword];
		}
		$response = \WpOrg\Requests\Requests::get($url, $headers, $options);
		$response->throw_for_status(false);
		return $response->body;
	}
}

class OrganizrArrClient extends OrganizrMediaClient
{
	private string $type;

	public function __construct($url, $apiKey, $type = 'sonarr', $httpAuthUsername = null, $httpAuthPassword = null, $options = [])
	{
		parent::__construct($url, $apiKey, $httpAuthUsername, $httpAuthPassword, $options);
		$this->type = strtolower((string)$type);
	}

	public function getCalendar($start = null, $end = null, $sonarrUnmonitored = 'false')
	{
		$query = [];
		foreach (['start' => $start, 'end' => $end] as $key => $date) {
			if ($date) {
				if (!$this->validateDate($date)) {
					return $this->error(ucfirst($key) . ' date string was not recognized as a valid DateTime. Format must be yyyy-mm-dd.', 400);
				}
				$query[$key] = $date;
			}
		}
		if ($sonarrUnmonitored == 'true') {
			$query['unmonitored'] = 'true';
		}
		if ($this->type == 'lidarr') {
			$query['includeArtist'] = 'true';
		}
		if ($this->type == 'sonarr') {
			$query['includeSeries'] = 'true';
		}
		return $this->request('calendar', $query);
	}

	public function getQueue()
	{
		$query = ['includeUnknownSeriesItems' => 'false', 'pageSize' => 1000];
		if ($this->type == 'sonarr') {
			$query['includeSeries'] = 'true';
			$query['includeEpisode'] = 'true';
		}
		return $this->request('queue', $query);
	}

	public function getRootFolder()
	{
		return $this->request('rootfolder');
	}

	private function request(string $uri, array $query = []): string
	{
		$version = match ($this->type) {
			'sonarr', 'radarr' => 'v3/',
			'lidarr' => 'v1/',
			default => '',
		};
		try {
			return $this->get($this->url . '/api/' . $version . $uri . '?' . http_build_query($query), ['X-Api-Key' => $this->apiKey]);
		} catch (\Exception $e) {
			return $this->error($e->getMessage(), $e->getCode());
		}
	}

	private function error(string $message, $code): string
	{
		return json_encode(['error' => ['msg' => $message, 'code' => $code]]);
	}

	private function validateDate($date, $format = 'Y-m-d'): bool
	{
		$d = \DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
}

class OrganizrSickRageClient extends OrganizrMediaClient
{
	public function future($sort = 'date', $type = 'missed|today|soon|later', $paused = null)
	{
		$query = ['sort' => $sort, 'type' => $type];
		if ($paused) {
			$query['paused'] = $paused;
		}
		return $this->command('future', $query);
	}

	public function history($limit = 100, $type = null)
	{
		$query = ['limit' => $limit];
		if ($type) {
			$query['type'] = $type;
		}
		return $this->command('history', $query);
	}

	public function sb()
	{
		return $this->command('sb');
	}

	private function command(string $cmd, array $query = []): string
	{
		return $this->get($this->url . '/api/' . $this->apiKey . '/?cmd=' . $cmd . '&' . http_build_query($query));
	}
}

class OrganizrCouchPotatoClient extends OrganizrMediaClient
{
	public function getMediaList(array $params = [])
	{
		$allowed = ['status', 'search', 'release_status', 'limit_offset', 'type', 'starts_with'];
		$query = array_intersect_key($params, array_flip($allowed));
		return $this->get($this->url . '/api/' . $this->apiKey . '/media.list?' . http_build_query($query));
	}
}
