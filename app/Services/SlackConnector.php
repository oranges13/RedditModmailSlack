<?php


namespace App\Services;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;

class SlackConnector
{
	private $client;

	/**
	 * SlackConnector constructor.
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Send the message collection to Slack Webhook
	 *
	 * @param Collection $messages
	 * @return int
	 * @throws Exception
	 */
	public function notify(Collection $messages)
	{
		if ($messages->isNotEmpty()) {
			try {
				$payload = $this->buildRequest($messages);

				// Send payload to slack webhook
				$this->client->post(env('SLACK_WEBHOOK_URL'), [
					'body' => $payload->toJson(),
				]);

			} catch (ConnectException | ClientException | RequestException $e) {
				throw new Exception("Unable to send notification to slack! " . $e->getResponse()->getBody(), null, $e);
			}
		}

		return $messages->count();
	}

	/**
	 * Build the request collection which is converted to JSON and sent to slack
	 *
	 * @param $messages
	 * @return Collection
	 */
	private function buildRequest(Collection $messages)
	{
		$payload = new Collection();
		$body = new Collection();

		// Add Section header
		$body->push([
			'type' => 'section',
			'text' => [
				'type' => "mrkdwn",
				'text' => '*Unread Modmail!*',
			]
		]);
		$body->push([
			'type' => 'divider',
		]);

		// Add individual messages
		$messages->each(function ($message) use ($body) {
			$body->push([
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => "*<{$message['link']}|{$message['subject']}>*\n{$message['text']}",
				]
			]);
			$body->push([
				'type' => 'section',
				'fields' => [
					[
						'type' => 'mrkdwn',
						'text' => "*Author*\n<https://reddit.com/u/{$message['author']}|/u/{$message['author']}>",
					],
					[
						'type' => 'mrkdwn',
						'text' => "*Posted*\n{$message['date']}",
					]
				]
			]);
			$body->push([
				'type' => 'divider',
			]);
		});

		// Add footer
		$body->push([
			'type' => 'context',
			'elements' => [
				[
					'type' => 'mrkdwn',
					'text' => '<https://mod.reddit.com/mail/all|Open Modmail> to view all messages',
				]
			]
		]);

		// Add blocks wrapper
		$payload->put('blocks', $body->toArray());

		return $payload;
	}
}