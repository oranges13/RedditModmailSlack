# Reddit Modmail Slack Notifier

A microservice which fetches a list of unread modmail messages from Reddit based on criteria you specify and then posts it using an incoming webhook to your slack channel!

## System Requirements

This application is built on the [Lumen Framework](https://lumen.laravel.com/docs/5.8) and has the same installation requirements:

* PHP >= 7.1.3
* OpenSSL PHP Extension
* PDO PHP Extension
* Mbstring PHP Extension

## Installation

Download the most recent release and expand the archive into your webserver root directory, or clone the `release` branch.

In the application base directory, run `composer install`.

Copy `.env.example` to `.env` and fill out the required configuration variables.

## Usage

### Quickstart

Send a `GET` request to the root of the application to run the service manually or you can use the artisan command
`artisan fetch:modmail {state}`. 

_State_ is an optional parameter (which defaults to 'new') which can be used to filter
the request for modmail.

### Automatic Scheduling

This application includes an artisan command that can be run on the schedule you desire.

If you wish to enable this functionality, you only need to uncomment the schedule line in
`app/Console/Commands/Kernel.php`

**Be sure to add the following cron entry to your server in order to run the scheduler once configured:**

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

If enabled, by default this task will run every 10 minutes. You can view the [available schedule frequencies](https://laravel.com/docs/5.8/scheduling#schedule-frequency-options)
in the official Laravel documentation

You can add constraints for the schedule or optionally add notifications on failure. All of these options are
documented in the [task Scheduling overview](https://laravel.com/docs/5.8/scheduling) in the official Laravel
Documentation

## Contributing

Bugfixes and improvements are welcome. Please submit issues using the template provided! 