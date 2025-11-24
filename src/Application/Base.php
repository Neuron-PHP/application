<?php

namespace Neuron\Application;

use Exception;
use Neuron\Data;
use Neuron\Data\Setting\SettingManager;
use Neuron\Data\Setting\Source\Env;
use Neuron\Data\Setting\Source\ISettingSource;
use Neuron\Log;
use Neuron\Log\ILogger;
use Neuron\Log\Logger;
use Neuron\Patterns\Registry;
use Neuron\Util;

/**
 * Base functionality for applications.
 */

abstract class Base implements IApplication
{
	private		string         	$_basePath;
	private		string         	$_eventListenersPath;
	private		?Registry			$_registry;
	protected	array					$_parameters;
	protected	?SettingManager	$_settings = null;
	protected	string				$_version;
	protected	bool					$_handleErrors = false;
	protected	bool					$_handleFatal  = false;
	protected	bool					$_crashed = false;

	/**
	 * Initial setup for the application.
	 *
	 * Loads the config file.
	 * Initializes the logger.
	 *
	 * @param string $version
	 * @param ISettingSource|null $source
	 * @throws Exception
	 */

	public function __construct( string $version, ?ISettingSource $source = null )
	{
		$this->_basePath = '.';

		$this->_registry = Registry::getInstance();

		$this->_version = $version;

		$this->initSettings( $source );

		date_default_timezone_set( $this->getSetting( 'system', 'timezone' ) ?? 'UTC' );

		$this->_eventListenersPath = $this->getSetting( 'events', 'listeners_path' ) ?? '';

		$this->initLogger();
	}

	/**
	 * @return bool
	 */
	public function getCrashed(): bool
	{
		return $this->_crashed;
	}

	/**
	 * @return string
	 */

	public function getEventListenersPath(): string
	{
		return $this->_eventListenersPath;
	}

	/**
	 * @param string $eventListenersPath
	 * @return Base
	 */

	public function setEventListenersPath( string $eventListenersPath ): Base
	{
		$this->_eventListenersPath = $eventListenersPath;
		return $this;
	}

	/**
	 * @return string
	 */

	public function getBasePath(): string
	{
		return $this->_basePath;
	}

	/**
	 * @param string $basePath
	 * @return Base
	 */

	public function setBasePath( string $basePath ): Base
	{
		$this->_basePath = $basePath;
		return $this;
	}

	/**
	 * Initializes the logger based on the parameters set in neuron.yaml.
	 * 	destination
	 * 	format
	 * 	file
	 * 	level
	 * @throws Exception
	 */

	public function initLogger(): void
	{
		/** @var Log\Log $log */
		$log = Log\Log::getInstance();

		$log->initIfNeeded();

		$log->logger->reset();

		// Create a new default logger using the destination and format
		// specified in the settings.

		$destClass   = $this->getSetting( 'logging', 'destination' );
		$formatClass = $this->getSetting( 'logging', 'format' );

		if( !$destClass || !$formatClass )
		{
			return;
		}

		$destination = new $destClass( new $formatClass() );

		$defaultLog = new Logger( $destination );

		$fileName = $this->getSetting( 'logging','file' );
		if( $fileName )
		{
			$destination->open(
				[
					'file_name' => $this->getBasePath().'/'.$fileName
				]
			);
		}

		$defaultLog->setRunLevel( $this->getSetting( 'logging', 'level' ) ?? (int)ILogger::DEBUG );

		$log->Logger->addLog( $defaultLog );

		$log->serialize();
	}

	/**
	 * @return bool
	 */

	public function willHandleErrors(): bool
	{
		return $this->_handleErrors;
	}

	/**
	 * @param bool $handleErrors
	 * @return Base
	 */

	public function setHandleErrors( bool $handleErrors ): Base
	{
		$this->_handleErrors = $handleErrors;
		return $this;
	}

	/**
	 * @return bool
	 */

	public function willHandleFatal(): bool
	{
		return $this->_handleFatal;
	}

	/**
	 * @param bool $handleFatal
	 * @return Base
	 */

	public function setHandleFatal( bool $handleFatal ): Base
	{
		$this->_handleFatal = $handleFatal;
		return $this;
	}

	/**
	 * @param ISettingSource $source
	 * @return $this
	 */

	public function setSettingSource( ISettingSource $source ) : Base
	{
		$this->_settings = new SettingManager( $source );
		return $this;
	}

	/**
	 * @param string $section
	 * @param string $name
	 * @return mixed
	 */
	public function getSetting( string $section, string $name ): mixed
	{
		return $this->_settings?->get( $section, $name );
	}

	/**
	 * @param string $section
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setSetting( string $section, string $name, string $value ): void
	{
		$this->_settings->set( $section, $name, $value );
	}

	/**
	 * @return ?SettingManager
	 */
	public function getSettingManager(): ?SettingManager
	{
		return $this->_settings;
	}

	/**
	 * Returns true if the application is running in command line mode.
	 * @return bool
	 */

	public function isCommandLine(): bool
	{
		return Util\System::isCommandLine();
	}

	/**
	 * Called before onRun.
	 *
	 * Initializes the event system and executes all initializers.
	 * If false is returned, application terminates without executing onRun.
	 * @return bool
	 */

	protected function onStart() : bool
	{
		Log\Log::debug( "onStart()" );

		$this->initEvents();
		$this->executeInitializers();
		return true;
	}

	/**
	 * Called immediately after onRun.
	 * @return void
	 */

	protected function onFinish(): void
	{
		Log\Log::debug( "onFinish()" );
	}

	/**
	 * Called for any unhandled exceptions.
	 * Returning false skips executing onFinish.
	 *
	 * @param string $message
	 * @return bool
	 */

	protected function onError( string $message ) : bool
	{
		Log\Log::error( "onError(): $message" );

		return true;
	}

	/**
	 * Called by the fatal handler if invoked.
	 *
	 * @param array $error
	 * @return void
	 */

	protected function onCrash( array $error ) : void
	{
		$this->_crashed = true;
		Log\Log::fatal( "onCrash(): ".$error[ 'message' ] );
	}

	/**
	 * Handler for fatal errors.
	 * Checks for actual fatal errors and formats them for display
	 * @return void
	 */

	public function fatalHandler(): void
	{
		$error = error_get_last();

		// Only handle actual fatal errors (not clean shutdowns)
		if( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ] ) )
		{
			// Get error type name
			$typeNames = [
				E_ERROR => 'Fatal Error',
				E_PARSE => 'Parse Error',
				E_CORE_ERROR => 'Core Error',
				E_COMPILE_ERROR => 'Compile Error',
				E_USER_ERROR => 'User Error'
			];

			$typeName = $typeNames[ $error['type'] ] ?? 'Unknown Fatal Error';

			// Call onCrash with detailed error information
			$this->onCrash([
				'type' => $typeName,
				'message' => $error['message'],
				'file' => $error['file'],
				'line' => $error['line']
			]);

			// Format output based on context (web vs CLI)
			echo $this->formatFatalError( $typeName, $error['message'], $error['file'], $error['line'] );
		}
	}

	/**
	 * Format fatal error for display
	 * Uses HTML for web, plain text for CLI
	 *
	 * @param string $type
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return string
	 */
	protected function formatFatalError( string $type, string $message, string $file, int $line ): string
	{
		if( $this->isCommandLine() )
		{
			// CLI format (plain text)
			$output = "\n";
			$output .= str_repeat( '=', 80 ) . "\n";
			$output .= "FATAL ERROR\n";
			$output .= str_repeat( '=', 80 ) . "\n\n";
			$output .= "Type:    $type\n";
			$output .= "Message: $message\n";
			$output .= "File:    $file\n";
			$output .= "Line:    $line\n";
			$output .= str_repeat( '=', 80 ) . "\n";

			return $output;
		}
		else
		{
			// Web format (HTML)
			$typeEsc = htmlspecialchars( $type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$messageEsc = htmlspecialchars( $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$fileEsc = htmlspecialchars( $file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<title>Fatal Error: $typeEsc</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
		.error-container { background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
		h1 { color: #c00; margin-top: 0; }
		.error-type { color: #666; font-size: 14px; margin-bottom: 20px; }
		.error-message { font-size: 18px; margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; }
		.error-location { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
		.error-location strong { color: #333; }
	</style>
</head>
<body>
	<div class="error-container">
		<h1>Fatal Error</h1>
		<div class="error-type">$typeEsc</div>
		<div class="error-message">$messageEsc</div>
		<div class="error-location">
			<strong>File:</strong> $fileEsc<br>
			<strong>Line:</strong> $line
		</div>
	</div>
</body>
</html>
HTML;
		}
	}

	/**
	 * Handler for PHP errors.
	 *
	 * @param int $errorNo
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return bool
	 */

	public function phpErrorHandler( int $errorNo, string $message, string $file, int $line) : bool
	{
		switch( $errorNo )
		{
			case E_NOTICE:
			case E_USER_NOTICE:
				$type = "Notice";
				break;

			case E_WARNING:
			case E_USER_WARNING:
				$type = "Warning";
				break;

			case E_ERROR:
			case E_USER_ERROR:
				$type = "Fatal Error";
				break;

			default:
				$type = "Unknown Error";
				break;
		}

		$this->onError( sprintf( "PHP %s:  %s in %s on line %d", $type, $message, $file, $line ));
		return true;
	}

	/**
	 * Global exception handler for uncaught exceptions and errors
	 * Handles both Exception and Error (PHP 7+)
	 *
	 * @param \Throwable $throwable
	 * @return void
	 */
	public function globalExceptionHandler( \Throwable $throwable ): void
	{
		// Call onCrash with error details (handles logging and state)
		$this->onCrash([
			'type' => get_class( $throwable ),
			'message' => $throwable->getMessage(),
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine(),
			'trace' => $throwable->getTraceAsString()
		]);

		// Output formatted error (HTML for web, plain text for CLI)
		echo $this->beautifyException( $throwable );

		exit( 1 );
	}

	/**
	 * Format exception/error for display
	 * Base implementation outputs plain text (CLI-friendly)
	 * MVC Application overrides this for HTML output
	 *
	 * @param \Throwable $throwable
	 * @return string
	 */
	public function beautifyException( \Throwable $throwable ): string
	{
		$type = get_class( $throwable );
		$message = $throwable->getMessage();
		$file = $throwable->getFile();
		$line = $throwable->getLine();
		$trace = $throwable->getTraceAsString();

		$output = "\n";
		$output .= str_repeat( '=', 80 ) . "\n";
		$output .= "APPLICATION ERROR\n";
		$output .= str_repeat( '=', 80 ) . "\n\n";
		$output .= "Type:    $type\n";
		$output .= "Message: $message\n";
		$output .= "File:    $file\n";
		$output .= "Line:    $line\n\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "Stack Trace:\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= $trace . "\n";
		$output .= str_repeat( '=', 80 ) . "\n";

		return $output;
	}

	/**
	 * Must be implemented by derived classes.
	 * @return void
	 */

	protected abstract function onRun() : void;

	/**
	 * Application version number.
	 * @return string
	 */

	public function getVersion() : string
	{
		return $this->_version;
	}

	/**
	 * Executes all initializer classes located in app/Initializers.
	 * @return void
	 */

	protected function executeInitializers(): void
	{
		Log\Log::debug( "executeInitializers()" );
		$initializer = new InitializerRunner( $this );
		$initializer->execute();
	}

	/**
	 * Loads event-listeners.yaml and maps all event listeners to their associated events.
	 * @return void
	 */

	public function initEvents(): void
	{
		Log\Log::debug( "initEvents()" );

		$eventLoader = new EventLoader( $this );
		$eventLoader->initEvents();
	}

	/**
	 * Call to run the application.
	 * @param array $argv
	 * @return bool
	 * @throws Exception
	 */

	public function run( array $argv = [] ): bool
	{
		$this->initErrorHandlers();

		$this->_parameters = $argv;

		if( !$this->onStart() )
		{
			Log\Log::fatal( "onStart() returned false. Aborting." );
			return false;
		}

		try
		{
			Log\Log::debug( "Running application v{$this->_version}.." );
			$this->onRun();
		}
		catch( Exception $exception )
		{
			$message = get_class( $exception ).', msg: '.$exception->getMessage();

			Log\Log::fatal( "Exception: $message" );

			$this->onCrash(
				[
					'message' => $message
				]
			);
		}

		$this->onFinish();
		return true;
	}

	/**
	 * returns parameters passed to the run method.
	 * @return array
	 */

	public function getParameters(): array
	{
		return $this->_parameters;
	}

	/**
	 * Gets a parameter by name.
	 * @param string $name
	 * @return mixed
	 */

	public function getParameter( string $name ): mixed
	{
		return $this->_parameters[ $name ];
	}

	/**
	 * @param string $name
	 * @param mixed $object
	 */

	public function setRegistryObject( string $name, mixed $object ): void
	{
		$this->_registry->set( $name, $object );
	}

	/**
	 * @param string $name
	 * @return mixed
	 */

	public function getRegistryObject( string $name ) : mixed
	{
		return $this->_registry->get( $name );
	}

	/**
	 * Sets up the php error and fatal handlers.
	 * @return void
	 */

	protected function initErrorHandlers(): void
	{
		if( $this->willHandleErrors() )
		{
			set_error_handler(
				[
					$this,
					'phpErrorHandler'
				]
			);
		}

		if( $this->willHandleFatal() )
		{
			register_shutdown_function(
				[
					$this,
					'fatalHandler'
				]
			);

			// Also set global exception handler for uncaught Throwables (Error & Exception)
			set_exception_handler(
				[
					$this,
					'globalExceptionHandler'
				]
			);
		}
	}

	/**
	 * @param ISettingSource|null $source
	 * @return void
	 */

	protected function initSettings( ?ISettingSource $source ): void
	{
		$this->_settings = Registry::getInstance()->get( 'Settings' );

		if( $this->_settings )
		{
			return;
		}

		$defaultBasePath = getenv( 'SYSTEM_BASE_PATH' ) ? : '.';
		$this->setBasePath( $defaultBasePath );
		$fallback = new Env( Data\Env::getInstance( "$defaultBasePath/.env" ) );

		if( !$source )
		{
			$this->_settings = new SettingManager( $fallback );
			Registry::getInstance()->set( 'Settings', $this->_settings );
			return;
		}

		try
		{
			$this->_settings = new SettingManager( $source );

			$basePath = $this->getSetting( 'system','base_path' ) ?? $defaultBasePath;
			$fallback = new Env( Data\Env::getInstance( "$basePath/.env" ) );
			$this->_settings->setFallback( $fallback );
			$this->setBasePath( $basePath );
		}
		catch( Exception $exception )
		{
			$this->_settings = new SettingManager( $fallback );
		}

		Registry::getInstance()->set( 'Settings', $this->_settings );
	}
}
