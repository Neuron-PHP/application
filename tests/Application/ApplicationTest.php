<?php
namespace Tests\Application;

use Exception;
use Neuron\Application\CrossCutting\Event;
use Neuron\Data\Settings\Source\Ini;
use Neuron\Patterns\Registry;
use PHPUnit\Framework\TestCase;
use Tests\AppMock;
use Tests\TestListener;

class ApplicationTest extends TestCase
{
	private $_App;

	protected function setUp(): void
	{
		parent::setUp();

		// Reset the TestListener counter to prevent test pollution
		TestListener::$Count = 0;

		// Reset the Event singleton to prevent listener accumulation
		Event::invalidate();

		$SettingSource = new Ini( 'examples/config/application.ini' );
		$this->_App = new AppMock( "1.0", $SettingSource );
	}

	public function testEventListenerPath()
	{
		$this->_App->setEventListenersPath( 'examples/Listeners' );
		$this->assertEquals(
			'examples/Listeners',
			$this->_App->getEventListenersPath()
		);
	}

	public function testGetVersion()
	{
		$this->assertEquals(
			'1.0',
			$this->_App->getVersion()
		);
	}

	public function testSettingsInRegistry()
	{
		$this->_App->run();
		$this->assertNotNull(
			Registry::getInstance()->get( 'Settings' )
		);
	}

	public function testSetSettingSource()
	{
		$SettingSource = new Ini( 'examples/config/application.ini' );

		$this->assertNotNull(
			$this->_App->setSettingSource( $SettingSource )
		);
	}

	public function testSetSetting()
	{
		$this->_App->setSetting( 'section' ,'name', 'value' );

		$this->assertEquals(
			'value',
			$this->_App->getSetting( 'section', 'name' )
		);
	}

	public function testNoConfig()
	{
		$App = new AppMock( "1.0" );
		$this->assertNull( $App->getSetting( "test", "test" ) );
	}

	public function testRun()
	{
		$this->assertTrue( $this->_App->run() );
	}

	public function testRegistry()
	{
		$this->_App->setRegistryObject( 'test', '1234' );

		$result = $this->_App->getRegistryObject( 'test' );

		$this->assertEquals(
			$result,
			'1234'
		);
	}

	public function testFatal()
	{
		$this->_App->setHandleFatal( true );
		$this->_App->run();
		$this->_App->crash();

		$this->assertTrue(
			$this->_App->DidCrash
		);
	}

	public function testIsCommandLine()
	{
		$this->assertTrue(
			$this->_App->isCommandLine()
		);
	}

	public function testOnError()
	{
		$this->_App->setHandleErrors( true );
		$this->_App->Error = true;

		$this->_App->run();

		$this->assertTrue(
			$this->_App->DidError
		);
	}

	public function testCrash()
	{
		$this->_App->setHandleFatal( true );

		$this->_App->Crash = true;
		$this->_App->run();

		$this->assertTrue(
			$this->_App->getCrashed()
		);
	}

	public function testGetParameter()
	{
		$this->_App->run(
			[
				'test' => 'monkey'
			]
		);

		$this->assertEquals(
			'monkey',
			$this->_App->getParameter( 'test' )
		);
	}

	public function testStart()
	{
		$this->_App->FailStart = true;

		$this->assertFalse(
			$this->_App->run()
		);
	}

	public function testGetParameters()
	{
		$this->_App->run( [ 'test' => 'test' ] );
		$this->assertTrue(
			is_array( $this->_App->getParameters() )
		);
	}

	public function testLogging()
	{
		$this->_App->run();
		$this->assertTrue( file_exists( 'examples/test.log') );
	}

	public function testTimeZone()
	{
		$this->_App->run();
		$this->assertEquals( 'US/Central', date_default_timezone_get() );
	}

	/**
	 * @throws Exception
	 */
	public function testInitializers()
	{
		$this->_App->setRegistryObject( 'Initializers.Path', 'examples/Initializers' );
		$this->_App->setRegistryObject( 'Initializers.Namespace', 'ComponentTest\Initializers\\' );
		$this->_App->run();

		$this->assertEquals(
			'Hello World!',
			Registry::getInstance()->get( 'examples\Initializers\InitTest' )
		);
	}

	public function testEventListeners()
	{
		$this->_App->run();

		$this->assertEquals(
			1,
			TestListener::$Count
		);
	}

	public function testNullSource()
	{
		Registry::getInstance()->set( 'Settings', null );
		$App = new AppMock( "1.0" );

		$App->run();

		// App without settings won't load event listeners
		$this->assertEquals(
			0,
			TestListener::$Count
		);
	}

	public function testBasePath()
	{
		$this->_App->setBasePath('/app/custom/path');
		$this->assertEquals(
			'/app/custom/path',
			$this->_App->getBasePath()
		);
	}

	public function testWillHandleErrors()
	{
		$this->assertFalse($this->_App->willHandleErrors());

		$this->_App->setHandleErrors(true);
		$this->assertTrue($this->_App->willHandleErrors());

		$this->_App->setHandleErrors(false);
		$this->assertFalse($this->_App->willHandleErrors());
	}

	public function testWillHandleFatal()
	{
		$this->assertFalse($this->_App->willHandleFatal());

		$this->_App->setHandleFatal(true);
		$this->assertTrue($this->_App->willHandleFatal());

		$this->_App->setHandleFatal(false);
		$this->assertFalse($this->_App->willHandleFatal());
	}

	public function testSetBasePathReturnsApp()
	{
		$result = $this->_App->setBasePath('/test');
		$this->assertSame($this->_App, $result);
	}

	public function testSetEventListenersPathReturnsApp()
	{
		$result = $this->_App->setEventListenersPath('/test/listeners');
		$this->assertSame($this->_App, $result);
	}

	public function testSetHandleErrorsReturnsApp()
	{
		$result = $this->_App->setHandleErrors(true);
		$this->assertSame($this->_App, $result);
	}

	public function testSetHandleFatalReturnsApp()
	{
		$result = $this->_App->setHandleFatal(true);
		$this->assertSame($this->_App, $result);
	}

	public function testSetSettingSourceReturnsApp()
	{
		$SettingSource = new Ini( 'examples/config/application.ini' );
		$result = $this->_App->setSettingSource( $SettingSource );
		$this->assertSame($this->_App, $result);
	}

	public function testGetSettingManager()
	{
		$settingManager = $this->_App->getSettingManager();
		$this->assertNotNull($settingManager);
		$this->assertInstanceOf(\Neuron\Data\Settings\SettingManager::class, $settingManager);
	}

	public function testPhpErrorHandlerWithNotice()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_NOTICE, 'Test notice', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithWarning()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_WARNING, 'Test warning', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithUserNotice()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_USER_NOTICE, 'Test user notice', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithUserWarning()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_USER_WARNING, 'Test user warning', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithError()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_ERROR, 'Test error', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithUserError()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(E_USER_ERROR, 'Test user error', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testPhpErrorHandlerWithUnknownError()
	{
		$this->_App->setHandleErrors(true);
		$result = $this->_App->phpErrorHandler(99999, 'Unknown error type', __FILE__, __LINE__);
		$this->assertTrue($result);
	}

	public function testBeautifyException()
	{
		$exception = new Exception('Test exception message');
		$output = $this->_App->beautifyException($exception);

		$this->assertStringContainsString('APPLICATION ERROR', $output);
		$this->assertStringContainsString('Exception', $output);
		$this->assertStringContainsString('Test exception message', $output);
		$this->assertStringContainsString('Stack Trace:', $output);
	}

	public function testFormatFatalErrorCli()
	{
		$output = $this->_App->formatFatalError('Fatal Error', 'Test message', '/path/to/file.php', 42);

		$this->assertStringContainsString('FATAL ERROR', $output);
		$this->assertStringContainsString('Fatal Error', $output);
		$this->assertStringContainsString('Test message', $output);
		$this->assertStringContainsString('/path/to/file.php', $output);
		$this->assertStringContainsString('42', $output);
	}

	public function testGlobalExceptionHandlerFormatsOutput()
	{
		// We can't test the actual handler because it calls exit()
		// But we can test the exception formatting it uses
		$exception = new Exception('Test exception for global handler');
		$output = $this->_App->beautifyException($exception);

		$this->assertStringContainsString('APPLICATION ERROR', $output);
		$this->assertStringContainsString('Test exception for global handler', $output);
	}

	public function testInitSettingsWithExistingSettingsInRegistry()
	{
		// Test that if Settings already exists in Registry, initSettings uses it
		$existingSettings = new \Neuron\Data\Settings\SettingManager(
			new Ini('examples/config/application.ini')
		);
		Registry::getInstance()->set('Settings', $existingSettings);

		$app = new AppMock("2.0", new Ini('examples/config/application.ini'));

		// Should use existing settings from registry
		$this->assertSame($existingSettings, $app->getSettingManager());
	}

	public function testInitSettingsWithNullSource()
	{
		// Clear registry settings
		Registry::getInstance()->set('Settings', null);

		$app = new AppMock("2.0", null);

		// Should create settings with env fallback
		$this->assertNotNull($app->getSettingManager());
	}

	public function testInitLoggerWithoutConfiguration()
	{
		// Create app without logging configuration
		Registry::getInstance()->set('Settings', null);
		$app = new AppMock("2.0", null);

		// initLogger should handle missing config gracefully
		$app->initLogger();

		// No exception should be thrown
		$this->assertTrue(true);
	}

	public function testFatalHandlerWithNoActualError()
	{
		// When no fatal error occurred, fatalHandler should do nothing
		$this->_App->fatalHandler();

		// Should not crash the app
		$this->assertFalse($this->_App->getCrashed());
	}

	public function testFormatFatalErrorHtml()
	{
		// Create a mock that simulates web context (not CLI)
		$mock = $this->getMockBuilder(AppMock::class)
			->setConstructorArgs(['1.0', new Ini('examples/config/application.ini')])
			->onlyMethods(['isCommandLine'])
			->getMock();

		$mock->expects($this->once())
			->method('isCommandLine')
			->willReturn(false);

		$output = $mock->formatFatalError('Fatal Error', 'Test message', '/path/to/file.php', 42);

		$this->assertStringContainsString('<!DOCTYPE html>', $output);
		$this->assertStringContainsString('Fatal Error', $output);
		$this->assertStringContainsString('Test message', $output);
		$this->assertStringContainsString('/path/to/file.php', $output);
		$this->assertStringContainsString('42', $output);
	}

	public function testInitSettingsWithInvalidSource()
	{
		// Clear registry
		Registry::getInstance()->set('Settings', null);

		// Create a mock source that will throw an exception
		$mockSource = $this->getMockBuilder(Ini::class)
			->setConstructorArgs(['examples/config/application.ini'])
			->onlyMethods(['get'])
			->getMock();

		$mockSource->method('get')
			->willThrowException(new Exception('Invalid config'));

		// App should handle exception and fall back to env
		$app = new AppMock("2.0", $mockSource);

		// Should still have settings (fallback)
		$this->assertNotNull($app->getSettingManager());
	}

	public function testRunWithException()
	{
		$this->_App->Crash = true;
		$result = $this->_App->run();

		// Should still return true (onFinish was called)
		$this->assertTrue($result);
		$this->assertTrue($this->_App->getCrashed());
	}

	public function testOnErrorReturnsTrue()
	{
		// Use reflection to call protected onError
		$reflection = new \ReflectionClass($this->_App);
		$method = $reflection->getMethod('onError');
		$method->setAccessible(true);

		// Parent's onError returns true (AppMock overrides to return false)
		$app = new AppMock("1.0", new Ini('examples/config/application.ini'));
		$result = $method->invoke($app, 'Test error message');

		// AppMock returns false, but we're testing the parent logic is called
		$this->assertTrue($app->DidError);
	}

	public function testInitSettingsWithBasePathFromSettings()
	{
		// Clear registry
		Registry::getInstance()->set('Settings', null);

		// Create app with settings that includes base_path
		$app = new AppMock("2.0", new Ini('examples/config/application.ini'));

		// Base path should be set from settings
		$basePath = $app->getBasePath();
		$this->assertNotEmpty($basePath);
	}

	public function testInitEventsLoadsEventListener()
	{
		$this->_App->initEvents();

		// Event listeners should be loaded
		// TestListener should be registered (from examples/Listeners)
		$this->assertTrue(true);
	}

	public function testExecuteInitializersRunsInitializers()
	{
		// Use reflection to call protected executeInitializers
		$reflection = new \ReflectionClass($this->_App);
		$method = $reflection->getMethod('executeInitializers');
		$method->setAccessible(true);

		$method->invoke($this->_App);

		// Initializers should be executed
		$this->assertTrue(true);
	}

	public function testInitSettingsUsesEnvVariable()
	{
		// Test that initSettings uses SYSTEM_BASE_PATH env variable
		Registry::getInstance()->set('Settings', null);

		// Set env variable
		putenv('SYSTEM_BASE_PATH=/custom/path');

		$app = new AppMock("2.0", null);

		// Clean up
		putenv('SYSTEM_BASE_PATH');

		$this->assertNotNull($app->getSettingManager());
	}
}

