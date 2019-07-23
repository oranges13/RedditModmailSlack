<?php

use App\Services\RedditConnector;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class RedditConnectorTest extends TestCase
{
	protected $reddit;
	protected $mockHandler;

	protected function setUp(): void
	{

		parent::setUp();

		// Set up required oauth request response on every test
		$this->mockHandler = new MockHandler([
			new Response(200, [], file_get_contents(__DIR__ . '/fixtures/redditOauthResponse.json'))
		]);

		$client = new Client([
			'handler' => $this->mockHandler
		]);

		$this->reddit = new RedditConnector($client);

	}

	protected function tearDown(): void
	{

		parent::tearDown();

		$this->reddit = null;
	}

	function testRetrievesMessages()
	{
		$this->mockHandler->append(new Response(200, [], file_get_contents(__DIR__ . '/fixtures/redditModmailResponse.json')));

		$modmail = $this->reddit->fetch();

		$this->assertCount(1, $modmail);
	}

	function testRecoversErrors()
	{
		$this->mockHandler->append(new ClientException("There was an error", new Request('GET', 'test'), new Response(401, [], file_get_contents(__DIR__ . '/fixtures/redditUnauthorized.json'))));

		$this->expectException(Exception::class);

		$this->reddit->fetch();

	}
}