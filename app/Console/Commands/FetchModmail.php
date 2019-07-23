<?php

namespace App\Console\Commands;

use App\Services\RedditConnector;
use App\Services\SlackConnector;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class FetchModmail extends Command
{
	private $reddit;
	private $slack;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'fetch:modmail 
		{state=new : The message state to filter by. One of (new, inprogress, mod, notifications, archived, highlighted, all) [default: new]}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get a list of new modmail messages and post them to the configured slack webhook';

	/**
	 * Create a new command instance.
	 */
	public function __construct()
	{
		parent::__construct();
		$redditClient = new Client([
			'headers' => [
				'User-Agent' => env('USER_AGENT_STRING'),
				'Content-Type' => 'application/x-www-form-urlencoded',
			]
		]);
		$slackClient = new Client([
			'headers' => [
				'User-Agent' => env('USER_AGENT_STRING'),
				'Content-Type' => 'application/json',
			]
		]);
		$this->reddit = new RedditConnector($redditClient);
		$this->slack = new SlackConnector($slackClient);
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle()
	{
		// Check for new mod mail messages
		try {
			$this->info('Checking for new modmail messages...');
			$modmail = $this->reddit->fetch($this->argument('state'));
			if ($modmail->isNotEmpty()) {
				$this->info("New messages found! Notifying Slack!");
				$count = $this->slack->notify($modmail);
				$this->info("Notified $count Messages!");
			} else {
				$this->warn("No new messages were fetched!");
			}
		} catch (Exception $e) {
			$this->error("Something went wrong!");
			$this->error($e->getMessage());
		}
	}

}