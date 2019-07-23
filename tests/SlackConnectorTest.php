<?php


use App\Services\SlackConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;

class SlackConnectorTest extends TestCase
{

	protected $slack;
	protected $mockHandler;

	protected function setUp(): void
	{
		parent::setUp();

		$this->mockHandler = new MockHandler();

		$client = new Client([
			'handler' => $this->mockHandler
		]);

		$this->slack = new SlackConnector($client);
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->slack = null;
	}

	function testSubmitsNotification()
	{

		$messages = new Collection([
			[
				'subject' => 'This is a test',
				'author' => 'test_mctesterson',
				'text' => 'Lorem ipsum dolor sit amet',
				'date' => 'Thu, Dec 25, 1975 2:15 PM',
				'link' => 'https://foo.com',
			]
		]);

		$this->mockHandler->append(new Response(200));

		$successCount = $this->slack->notify($messages);

		$this->assertEquals($messages->count(), $successCount);
	}

	function testRecoversFromErrors()
	{
		$messages = new Collection([
			[
				'subject' => 'This is a test',
			]
		]);

		$this->expectException(ErrorException::class);

		// Calling notify with bad data will produce exception
		$this->slack->notify($messages);

	}
}