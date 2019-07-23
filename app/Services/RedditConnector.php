<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RedditConnector
{

	private $client;
	private $access_token;
	private $results;

	/**
	 * RedditConnector constructor.
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{

		$this->client = $client;
		$this->access_token = Cache::remember('reddit.auth.token', 3600, function () {
			return $this->refreshToken();
		});
		$this->results = new Collection();
	}

	/**
	 * Fetch modmail from Reddit
	 *
	 * @param String $state The modmail message state to filter by
	 * @return Collection
	 * @throws Exception
	 */
	public function fetch($state = 'new')
	{

		try {
			$response = $this->client->get('https://oauth.reddit.com/api/mod/conversations',
				[
					'query' => [
						'entity' => env('SUBS_TO_NOTIFY'),
						'state' => $state,
					],
					'headers' => [
						'Authorization' => 'bearer ' . $this->access_token,
					]
				]
			);

			//Successful response so parse the JSON plz
			if ($response->getStatusCode() == 200) {
				$body = json_decode($response->getBody(), true);

				// Respond to errors
				if (array_key_exists('error', $body)) {
					// The Reddit API returns status code 200 with errors...?
					throw new Exception("Unable to retrieve new modmail! $body->error");
				}

				// Only do work if conversations exist
				foreach ($body['conversations'] as $id => $conversation) {
					// Get message data for this conversation
					foreach (array_column($conversation['objIds'], 'id') as $messageId) {
						$this->results->push([
							'subject' => $conversation['subject'],
							'author' => $body['messages'][$messageId]['author']['name'],
							'text' => Str::limit($body['messages'][$messageId]['bodyMarkdown'], 200, '...'),
							'date' => Carbon::parse($body['messages'][$messageId]['date'])->toDayDateTimeString(),
							'link' => 'https://mod.reddit.com/mail/perma/' . $id . '/' . $messageId
						]);
					}
				}
			}
		} catch (ConnectException | ClientException | RequestException $e) {
			throw new Exception("Unable to fetch modmail from reddit." . $e->getResponse()->getBody(), 0, $e);
		}

		return $this->results;
	}

	/**
	 * Refresh the Reddit Auth Token.
	 *
	 * @return string
	 * @throws Exception
	 */
	private function refreshToken()
	{
		$response = $this->client->post('https://www.reddit.com/api/v1/access_token',
			[
				'auth' => [
					env('REDDIT_APP_ID'),
					env('REDDIT_APP_SECRET'),
				],
				'form_params' => [
					'grant_type' => 'refresh_token',
					'refresh_token' => env('REFRESH_TOKEN'),
				],
			]
		);

		if ($response->getStatusCode() == 200) {
			$body = json_decode($response->getBody());
			if (property_exists($body, 'error')) {
				// The Reddit API returns status code 200 with errors...?
				throw new Exception("Unable to refresh access token $body->error");
			}
			return $body->access_token;
		} else {
			throw new Exception("Unable to refresh access token!");
		}
	}

}