<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(Client::class, function ($app) {
			$client = new Client([
				'headers' => [
					'User-Agent' => env('REDDIT_USER_AGENT'),
					'Content-Type' => 'application/x-www-form-urlencoded',
				]
			]);
			return $client;
		});
	}
}
