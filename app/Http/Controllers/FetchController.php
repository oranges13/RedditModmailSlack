<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;

class FetchController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client(
        	[
        		'defaults' => [
					'headers' => [
						'User-Agent' => env('REDDIT_USER_AGENT'),
					]
				]
			]
		);
        $this->access_token = '';
    }

    public function getConversations() {
		// Check current auth token
		$this->refreshToken();
		$response = $this->client->get('https://oauth.reddit.com/api/mod/conversations',
			[
				'query' => [
					'entity' => env('SUBS_TO_NOTIFY')
				],
				'headers' => [
					'Authorization' => 'bearer ' . $this->access_token,
					'User-Agent' => env('REDDIT_USER_AGENT'),
				]
			]
		);

		//Successful response so parse the JSON plz
		if($response->getStatusCode() == 200) {
			$body = json_decode($response->getBody(), true);
			foreach($body->conversations as $id => $conversation) {
				// Get message preview and show conversation information
			}
		}
	}

	private function refreshToken() {
		$response = $this->client->post('https://www.reddit.com/api/v1/access_token',
			[
				'auth' => [
					env('REDDIT_APP_ID'),
					env('REDDIT_APP_SECRET'),
				],
				'form_params' => [
					'grant_type' => 'refresh',
					'refresh_token' => env('REFRESH_TOKEN'),
				],
			]
		);

		if($response->getStatusCode() == 200) {
			$body = json_decode($response->getBody());
			$this->access_token = $body->access_token;
		}
	}

	private function notifySlack($messages) {

	}
}
