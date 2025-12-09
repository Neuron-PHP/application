<?php

namespace Tests\Application\Events;

use Neuron\Application\Events\ApplicationCrashedEvent;
use Neuron\Application\Events\ApplicationShuttingDownEvent;
use Neuron\Application\Events\ErrorOccurredEvent;
use Neuron\Application\Events\FatalErrorEvent;
use Neuron\Events\IEvent;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for Application Event classes.
 *
 * Tests all application event value objects with readonly properties.
 */
class EventsTest extends TestCase
{
	// ========================================================================
	// FatalErrorEvent Tests
	// ========================================================================

	public function testFatalErrorEventCanBeCreated(): void
	{
		$event = new FatalErrorEvent(
			'Fatal Error',
			'Call to undefined function',
			'/path/to/file.php',
			42
		);

		$this->assertInstanceOf(IEvent::class, $event);
		$this->assertEquals('Fatal Error', $event->type);
		$this->assertEquals('Call to undefined function', $event->message);
		$this->assertEquals('/path/to/file.php', $event->file);
		$this->assertEquals(42, $event->line);
	}

	public function testFatalErrorEventGetName(): void
	{
		$event = new FatalErrorEvent('Error', 'Message', 'file.php', 1);

		$this->assertEquals('application.fatal_error', $event->getName());
	}

	public function testFatalErrorEventWithParseError(): void
	{
		$event = new FatalErrorEvent(
			'Parse Error',
			'syntax error, unexpected \'}\'',
			'/app/src/Model.php',
			123
		);

		$this->assertEquals('Parse Error', $event->type);
		$this->assertEquals('syntax error, unexpected \'}\'', $event->message);
		$this->assertEquals('/app/src/Model.php', $event->file);
		$this->assertEquals(123, $event->line);
	}

	public function testFatalErrorEventWithLongPath(): void
	{
		$longPath = '/very/long/path/to/application/src/controllers/admin/UserController.php';
		$event = new FatalErrorEvent(
			'Fatal Error',
			'Maximum execution time exceeded',
			$longPath,
			999
		);

		$this->assertEquals($longPath, $event->file);
		$this->assertEquals(999, $event->line);
	}

	// ========================================================================
	// ApplicationCrashedEvent Tests
	// ========================================================================

	public function testApplicationCrashedEventCanBeCreated(): void
	{
		$error = [
			'type' => 'Fatal Error',
			'message' => 'Uncaught exception',
			'file' => '/path/to/app.php',
			'line' => 100,
			'trace' => 'Stack trace...'
		];

		$event = new ApplicationCrashedEvent($error);

		$this->assertInstanceOf(IEvent::class, $event);
		$this->assertEquals($error, $event->error);
		$this->assertArrayHasKey('type', $event->error);
		$this->assertArrayHasKey('message', $event->error);
		$this->assertArrayHasKey('file', $event->error);
		$this->assertArrayHasKey('line', $event->error);
		$this->assertArrayHasKey('trace', $event->error);
	}

	public function testApplicationCrashedEventGetName(): void
	{
		$event = new ApplicationCrashedEvent(['type' => 'Error']);

		$this->assertEquals('application.crashed', $event->getName());
	}

	public function testApplicationCrashedEventWithMinimalError(): void
	{
		$error = [
			'type' => 'Exception',
			'message' => 'Something went wrong'
		];

		$event = new ApplicationCrashedEvent($error);

		$this->assertEquals('Exception', $event->error['type']);
		$this->assertEquals('Something went wrong', $event->error['message']);
	}

	public function testApplicationCrashedEventWithCompleteError(): void
	{
		$error = [
			'type' => 'RuntimeException',
			'message' => 'Database connection failed',
			'file' => '/app/src/Database.php',
			'line' => 50,
			'trace' => 'Full stack trace here...',
			'code' => 1062,
			'previous' => null
		];

		$event = new ApplicationCrashedEvent($error);

		$this->assertCount(7, $event->error);
		$this->assertEquals('RuntimeException', $event->error['type']);
		$this->assertEquals(1062, $event->error['code']);
	}

	// ========================================================================
	// ErrorOccurredEvent Tests
	// ========================================================================

	public function testErrorOccurredEventCanBeCreated(): void
	{
		$event = new ErrorOccurredEvent(
			E_NOTICE,
			'Undefined variable: foo',
			'/app/controller.php',
			25
		);

		$this->assertInstanceOf(IEvent::class, $event);
		$this->assertEquals(E_NOTICE, $event->errorNo);
		$this->assertEquals('Undefined variable: foo', $event->message);
		$this->assertEquals('/app/controller.php', $event->file);
		$this->assertEquals(25, $event->line);
	}

	public function testErrorOccurredEventGetName(): void
	{
		$event = new ErrorOccurredEvent(E_WARNING, 'Warning', 'file.php', 1);

		$this->assertEquals('application.error', $event->getName());
	}

	public function testErrorOccurredEventWithNotice(): void
	{
		$event = new ErrorOccurredEvent(
			E_NOTICE,
			'Use of undefined constant - assumed \'TEST\'',
			'/app/config.php',
			10
		);

		$this->assertEquals(E_NOTICE, $event->errorNo);
		$this->assertStringContainsString('undefined constant', $event->message);
	}

	public function testErrorOccurredEventWithWarning(): void
	{
		$event = new ErrorOccurredEvent(
			E_WARNING,
			'Division by zero',
			'/app/calculator.php',
			55
		);

		$this->assertEquals(E_WARNING, $event->errorNo);
		$this->assertEquals('Division by zero', $event->message);
	}

	public function testErrorOccurredEventWithUserError(): void
	{
		$event = new ErrorOccurredEvent(
			E_USER_ERROR,
			'Invalid input provided',
			'/app/validator.php',
			77
		);

		$this->assertEquals(E_USER_ERROR, $event->errorNo);
		$this->assertEquals('Invalid input provided', $event->message);
		$this->assertEquals(77, $event->line);
	}

	// ========================================================================
	// ApplicationShuttingDownEvent Tests
	// ========================================================================

	public function testApplicationShuttingDownEventCanBeCreated(): void
	{
		$event = new ApplicationShuttingDownEvent();

		$this->assertInstanceOf(IEvent::class, $event);
	}

	public function testApplicationShuttingDownEventGetName(): void
	{
		$event = new ApplicationShuttingDownEvent();

		$this->assertEquals('application.shutting_down', $event->getName());
	}

	public function testApplicationShuttingDownEventMultipleInstances(): void
	{
		$event1 = new ApplicationShuttingDownEvent();
		$event2 = new ApplicationShuttingDownEvent();

		$this->assertInstanceOf(ApplicationShuttingDownEvent::class, $event1);
		$this->assertInstanceOf(ApplicationShuttingDownEvent::class, $event2);
		$this->assertNotSame($event1, $event2);
		$this->assertEquals($event1->getName(), $event2->getName());
	}

	// ========================================================================
	// Event Name Uniqueness Tests
	// ========================================================================

	public function testAllEventNamesAreUnique(): void
	{
		$fatalError = new FatalErrorEvent('Error', 'msg', 'file', 1);
		$crashed = new ApplicationCrashedEvent([]);
		$error = new ErrorOccurredEvent(E_NOTICE, 'msg', 'file', 1);
		$shutdown = new ApplicationShuttingDownEvent();

		$names = [
			$fatalError->getName(),
			$crashed->getName(),
			$error->getName(),
			$shutdown->getName()
		];

		// All event names should be unique
		$this->assertCount(4, array_unique($names));
		$this->assertEquals(['application.fatal_error', 'application.crashed', 'application.error', 'application.shutting_down'], $names);
	}
}
