<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FetchController extends Controller
{

	private $client;
	private $access_token;
	private $results;

	/**
	 * Set up Guzzle Client for requests
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->client = new Client(
			[
				'headers' => [
					'User-Agent' => env('REDDIT_USER_AGENT'),
					'Content-Type' => 'application/x-www-form-urlencoded',
				]
			]
		);
		$this->access_token = '';
		$this->results = [];
	}

	/**
	 * Respond to API request and do the work
	 *
	 * @see FetchController::refreshToken()
	 * @see FetchController::notifySlack()
	 *
	 * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
	 */
	public function getConversations()
	{
		// Check current auth token
		$this->refreshToken();

		// Check for new mod mail messages
		try {
			$response = $this->client->get('https://oauth.reddit.com/api/mod/conversations',
				[
					'query' => [
						'entity' => env('SUBS_TO_NOTIFY'),
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
					return response($body->error, 400);
				}

				// Only do work if conversations exist
				foreach ($body['conversations'] as $id => $conversation) {
					// Get message data for this conversation
					foreach (array_column($conversation['objIds'], 'id') as $messageId) {
						$this->results[] = [
							'subject' => $conversation['subject'],
							'author' => $body['messages'][$messageId]['author']['name'],
							'text' => Str::limit($body['messages'][$messageId]['bodyMarkdown'], 200, '...'),
							'date' => Carbon::parse($body['messages'][$messageId]['date'])->toDayDateTimeString(),
							'link' => 'https://mod.reddit.com/mail/perma/' . $id . '/' . $messageId
						];
					}
				}
				$this->notifySlack();
			}
		} catch (ClientException $e) {
			return response($e->getMessage(), 400);
		}
		$countResults = count($this->results);
		return response("Notified $countResults messages!");
	}

	/**
	 * Refresh the Reddit Auth Token.
	 *
	 * @return void
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
				response($body->error, 400)->send();
				exit();
			}
			$this->access_token = $body->access_token;
		}
	}

	/**
	 * Build the Slack Webhook JSON Payload and notify Slack Webhook.
	 *
	 * @return void
	 */
	private function notifySlack()
	{
		// Build Slack Webhook JSON
		if (count($this->results) > 0) {
			$body = [
				[
					'type' => 'section',
					'text' => [
						'type' => "mrkdwn",
						'text' => '*Unread Modmail!*',
					]
				],
				[
					'type' => 'divider',
				],
			];
			foreach ($this->results as $message) {
				$body[] = [
					'type' => 'section',
					'text' => [
						'type' => 'mrkdwn',
						'text' => "*<{$message['link']}|{$message['subject']}>*\n{$message['text']}",
					]
				];
				$body[] = [
					'type' => 'section',
					'fields' => [
						[
							'type' => 'mrkdwn',
							'text' => "*Author*\n{$message['author']}",
						],
						[
							'type' => 'mrkdwn',
							'text' => "*Posted*\n{$message['date']}",
						]
					]
				];
				$body[] = [
					'type' => 'divider',
				];
			}
			$body[] = [
				'type' => 'context',
				'elements' => [
					[
						'type' => 'mrkdwn',
						'text' => '<https://mod.reddit.com/mail/|View Modmail>',
					]
				]
			];

			$payload['blocks'] = $body;

			try {
				// Send payload to slack webhook
				$this->client->post(env('SLACK_WEBHOOK_URL'), [
					'body' => json_encode($payload),
					'headers' => [
						'Content-Type' => 'application/json',
					]
				]);
			} catch (ClientException $e) {
				response($e->getMessage(), 400)->send();
				exit();
			} catch (RequestException $e) {
				response($e->getMessage(), 400)->send();
				exit();
			}
		}
	}
}
