<?php

namespace Tests\Application;

use Neuron\Data\Settings\Source\Ini;
use PHPUnit\Framework\TestCase;
use Tests\AppMock;

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
}
