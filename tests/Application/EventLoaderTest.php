<?php

namespace Tests\Application;

use Neuron\Application\CrossCutting\Event;
use Neuron\Data\Settings\Source\Ini;
use Neuron\Events\Broadcasters\Base as BroadcasterBase;
use Neuron\Events\Broadcasters\Generic;
use Neuron\Events\IEvent;
use PHPUnit\Framework\TestCase;
use Tests\AppMock;
use Tests\TestEvent;
use Tests\TestListener;

/**
 * A broadcaster of another kind - one that would forward events elsewhere.
 * Counts what it is given instead.
 */
class ForwardingBroadcaster extends BroadcasterBase
{
	public int $Forwarded = 0;

	public function broadcast( IEvent $event ) : void
	{
		foreach( $this->listeners[ get_class( $event ) ] ?? [] as $listener )
		{
			$this->Forwarded++;
		}
	}
}

class EventLoaderTest extends TestCase
{
	private $tempDir;

	protected function setUp(): void
	{
		parent::setUp();

		// Create temporary directory for test files
		$this->tempDir = sys_get_temp_dir() . '/neuron_eventloader_test_' . uniqid();
		mkdir($this->tempDir);
		mkdir($this->tempDir . '/config');
	}

	protected function tearDown(): void
	{
		// Clean up temporary directory
		if (file_exists($this->tempDir . '/config/event-listeners.yaml')) {
			unlink($this->tempDir . '/config/event-listeners.yaml');
		}
		if (is_dir($this->tempDir . '/config')) {
			rmdir($this->tempDir . '/config');
		}
		if (is_dir($this->tempDir)) {
			rmdir($this->tempDir);
		}

		parent::tearDown();
	}

	public function testInitEventsWithInvalidYaml(): void
	{
		// Create completely malformed content that will cause ParseException
		$invalidYaml = "this is not valid YAML at all: @#$%^&*()";
		file_put_contents($this->tempDir . '/config/event-listeners.yaml', $invalidYaml);

		// Create app with custom base path
		$app = new AppMock("1.0", new Ini('examples/config/application.ini'));
		$app->setBasePath($this->tempDir);

		// Should handle parse exception gracefully
		$app->initEvents();

		// Should not throw exception
		$this->assertTrue(true);
	}

	public function testInitEventsWithMissingFile(): void
	{
		// No event-listeners.yaml file exists

		// Create app with custom base path
		$app = new AppMock("1.0", new Ini('examples/config/application.ini'));
		$app->setBasePath($this->tempDir);

		// Should handle missing file gracefully
		$app->initEvents();

		// Should not throw exception
		$this->assertTrue(true);
	}

	private function appWithListener(): AppMock
	{
		file_put_contents(
			$this->tempDir . '/config/event-listeners.yaml',
			"events:\n  test:\n    class: 'Tests\\TestEvent'\n    listeners:\n      - 'Tests\\TestListener'\n"
		);

		$app = new AppMock("1.0", new Ini('examples/config/application.ini'));
		$app->setBasePath($this->tempDir);

		return $app;
	}

	/**
	 * Loading the listeners again - a second application started in the same
	 * process - used to register a second Generic broadcaster, which got every
	 * listener again, so each event reached each listener twice.
	 */
	public function testLoadingTwiceStillDeliversEachEventOnce(): void
	{
		Event::invalidate();
		TestListener::$Count = 0;

		$app = $this->appWithListener();
		$app->initEvents();
		$app->initEvents();

		Event::emit(new TestEvent());

		$this->assertSame(1, TestListener::$Count);

		$generic = array_filter(Event::getBroadcasters(), fn($b) => $b instanceof Generic);
		$this->assertCount(1, $generic);

		Event::invalidate();
	}

	public function testABroadcasterOfAnotherKindIsKeptAlongside(): void
	{
		Event::invalidate();
		TestListener::$Count = 0;

		$forwarding = new ForwardingBroadcaster();
		Event::registerBroadcaster($forwarding);

		$this->appWithListener()->initEvents();

		Event::emit(new TestEvent());

		$this->assertSame(1, TestListener::$Count, 'the Generic one was still added');
		$this->assertSame(1, $forwarding->Forwarded);
		$this->assertCount(2, Event::getBroadcasters());

		Event::invalidate();
	}
}
