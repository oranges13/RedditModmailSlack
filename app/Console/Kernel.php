<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		Commands\FetchModmail::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param Schedule $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		// Uncomment this to enable the automatic command schedule
		// You can view the available schedule options at https://laravel.com/docs/5.8/scheduling#schedule-frequency-options
		// $schedule->command('fetch:modmail')->everyTenMinutes();
	}
}
