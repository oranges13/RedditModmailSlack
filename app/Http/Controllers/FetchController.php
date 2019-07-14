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
						'Authorization' => 'bearer ' . env('REDDIT_TOKEN'),
						'User-Agent' => 'PR modmail checker by /u/oranges13',
					]
				]
			]
		);

    }

    public function getConversations() {
		// Check current auth token
		$response = $this->client->request('GET', 'https://oauth.reddit.com/api/mod/conversations',
			[
				'query' => [
					'state' => 'archived',
					'entity' => env('SUBS_TO_NOTIFY')
				],
				'headers' => [
					'Authorization' => 'bearer ' . env('REDDIT_TOKEN'),
					'User-Agent' => 'PR modmail checker by /u/oranges13',
				]
			]
		);

		//Successful response so parse the JSON plz

	}
}
