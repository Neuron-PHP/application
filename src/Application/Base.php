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
	private		string         $_BasePath;
	private		string         $_EventListenersPath;
	private		?Registry			$_Registry;
	protected	array					$_Parameters;
	protected	?SettingManager	$_Settings = null;
	protected	string				$_Version;
	protected	bool					$_HandleErrors = false;
	protected	bool					$_HandleFatal  = false;
	protected	bool					$_Crashed = false;

	/**
	 * Initial setup for the application.
	 *
	 * Loads the config file.
	 * Initializes the logger.
	 *
	 * @param string $Version
	 * @param ISettingSource|null $Source
	 * @throws Exception
	 */

	public function __construct( string $Version, ?ISettingSource $Source = null )
	{
		$this->_BasePath = '.';

		$this->_Registry = Registry::getInstance();

		$this->_Version = $Version;

		$this->initSettings( $Source );

		date_default_timezone_set( $this->getSetting( 'system', 'timezone' ) ?? 'UTC' );

		$this->_EventListenersPath = $this->getSetting( 'events', 'listeners_path' ) ?? '';

		$this->initLogger();
	}

	/**
	 * @return bool
	 */
	public function getCrashed(): bool
	{
		return $this->_Crashed;
	}

	/**
	 * @return string
	 */

	public function getEventListenersPath(): string
	{
		return $this->_EventListenersPath;
	}

	/**
	 * @param string $EventListenersPath
	 * @return Base
	 */

	public function setEventListenersPath( string $EventListenersPath ): Base
	{
		$this->_EventListenersPath = $EventListenersPath;
		return $this;
	}

	/**
	 * @return string
	 */

	public function getBasePath(): string
	{
		return $this->_BasePath;
	}

	/**
	 * @param string $BasePath
	 * @return Base
	 */

	public function setBasePath( string $BasePath ): Base
	{
		$this->_BasePath = $BasePath;
		return $this;
	}

	/**
	 * Initializes the logger based on the parameters set in config.yaml.
	 * 	destination
	 * 	format
	 * 	file
	 * 	level
	 * @throws Exception
	 */

	public function initLogger(): void
	{
		/** @var Log\Log $Log */
		$Log = Log\Log::getInstance();

		$Log->initIfNeeded();

		$Log->Logger->reset();

		// Create a new default logger using the destination and format
		// specified in the settings.

		$DestClass   = $this->getSetting( 'logging', 'destination' );
		$FormatClass = $this->getSetting( 'logging', 'format' );

		if( !$DestClass || !$FormatClass )
		{
			return;
		}

		$Destination = new $DestClass( new $FormatClass() );

		$DefaultLog = new Logger( $Destination );

		$FileName = $this->getSetting( 'logging','file' );
		if( $FileName )
		{
			$Destination->open(
				[
					'file_name' => $this->getBasePath().'/'.$FileName
				]
			);
		}

		$DefaultLog->setRunLevel( $this->getSetting( 'logging', 'level' ) ?? (int)ILogger::DEBUG );

		$Log->Logger->addLog( $DefaultLog );

		$Log->serialize();
	}

	/**
	 * @return bool
	 */

	public function willHandleErrors(): bool
	{
		return $this->_HandleErrors;
	}

	/**
	 * @param bool $HandleErrors
	 * @return Base
	 */

	public function setHandleErrors( bool $HandleErrors ): Base
	{
		$this->_HandleErrors = $HandleErrors;
		return $this;
	}

	/**
	 * @return bool
	 */

	public function willHandleFatal(): bool
	{
		return $this->_HandleFatal;
	}

	/**
	 * @param bool $HandleFatal
	 * @return Base
	 */

	public function setHandleFatal( bool $HandleFatal ): Base
	{
		$this->_HandleFatal = $HandleFatal;
		return $this;
	}

	/**
	 * @param ISettingSource $Source
	 * @return $this
	 */

	public function setSettingSource( ISettingSource $Source ) : Base
	{
		$this->_Settings = new SettingManager( $Source );
		return $this;
	}

	/**
	 * @param string $section
	 * @param string $name
	 * @return mixed
	 */
	public function getSetting( string $section, string $name ): mixed
	{
		return $this->_Settings?->get( $section, $name );
	}

	/**
	 * @param string $section
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setSetting( string $section, string $name, string $value ): void
	{
		$this->_Settings->set( $section, $name, $value );
	}

	/**
	 * @return ?SettingManager
	 */
	public function getSettingManager(): ?SettingManager
	{
		return $this->_Settings;
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
	 * @param string $Message
	 * @return bool
	 */

	protected function onError( string $Message ) : bool
	{
		Log\Log::error( "onError(): $Message" );

		return true;
	}

	/**
	 * Called by the fatal handler if invoked.
	 *
	 * @param array $Error
	 * @return void
	 */

	protected function onCrash( array $Error ) : void
	{
		$this->_Crashed = true;
		Log\Log::fatal( "onCrash(): ".$Error[ 'message' ] );
	}

	/**
	 * Handler for fatal errors.
	 * Checks for actual fatal errors and formats them for display
	 * @return void
	 */

	public function fatalHandler(): void
	{
		$Error = error_get_last();

		// Only handle actual fatal errors (not clean shutdowns)
		if( $Error && in_array( $Error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ] ) )
		{
			// Get error type name
			$TypeNames = [
				E_ERROR => 'Fatal Error',
				E_PARSE => 'Parse Error',
				E_CORE_ERROR => 'Core Error',
				E_COMPILE_ERROR => 'Compile Error',
				E_USER_ERROR => 'User Error'
			];

			$TypeName = $TypeNames[ $Error['type'] ] ?? 'Unknown Fatal Error';

			// Call onCrash with detailed error information
			$this->onCrash([
				'type' => $TypeName,
				'message' => $Error['message'],
				'file' => $Error['file'],
				'line' => $Error['line']
			]);

			// Format output based on context (web vs CLI)
			echo $this->formatFatalError( $TypeName, $Error['message'], $Error['file'], $Error['line'] );
		}
	}

	/**
	 * Format fatal error for display
	 * Uses HTML for web, plain text for CLI
	 *
	 * @param string $Type
	 * @param string $Message
	 * @param string $File
	 * @param int $Line
	 * @return string
	 */
	protected function formatFatalError( string $Type, string $Message, string $File, int $Line ): string
	{
		if( $this->isCommandLine() )
		{
			// CLI format (plain text)
			$Output = "\n";
			$Output .= str_repeat( '=', 80 ) . "\n";
			$Output .= "FATAL ERROR\n";
			$Output .= str_repeat( '=', 80 ) . "\n\n";
			$Output .= "Type:    $Type\n";
			$Output .= "Message: $Message\n";
			$Output .= "File:    $File\n";
			$Output .= "Line:    $Line\n";
			$Output .= str_repeat( '=', 80 ) . "\n";

			return $Output;
		}
		else
		{
			// Web format (HTML)
			$TypeEsc = htmlspecialchars( $Type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$MessageEsc = htmlspecialchars( $Message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$FileEsc = htmlspecialchars( $File, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			return <<<HTML
<!DOCTYPE html>
<html>
<head>
	<title>Fatal Error: $TypeEsc</title>
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
		<div class="error-type">$TypeEsc</div>
		<div class="error-message">$MessageEsc</div>
		<div class="error-location">
			<strong>File:</strong> $FileEsc<br>
			<strong>Line:</strong> $Line
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
	 * @param int $ErrorNo
	 * @param string $Message
	 * @param string $File
	 * @param int $Line
	 * @return bool
	 */

	public function phpErrorHandler( int $ErrorNo, string $Message, string $File, int $Line) : bool
	{
		switch( $ErrorNo )
		{
			case E_NOTICE:
			case E_USER_NOTICE:
				$Type = "Notice";
				break;

			case E_WARNING:
			case E_USER_WARNING:
				$Type = "Warning";
				break;

			case E_ERROR:
			case E_USER_ERROR:
				$Type = "Fatal Error";
				break;

			default:
				$Type = "Unknown Error";
				break;
		}

		$this->onError( sprintf( "PHP %s:  %s in %s on line %d", $Type, $Message, $File, $Line ));
		return true;
	}

	/**
	 * Global exception handler for uncaught exceptions and errors
	 * Handles both Exception and Error (PHP 7+)
	 *
	 * @param \Throwable $Throwable
	 * @return void
	 */
	public function globalExceptionHandler( \Throwable $Throwable ): void
	{
		// Call onCrash with error details (handles logging and state)
		$this->onCrash([
			'type' => get_class( $Throwable ),
			'message' => $Throwable->getMessage(),
			'file' => $Throwable->getFile(),
			'line' => $Throwable->getLine(),
			'trace' => $Throwable->getTraceAsString()
		]);

		// Output formatted error (HTML for web, plain text for CLI)
		echo $this->beautifyException( $Throwable );

		exit( 1 );
	}

	/**
	 * Format exception/error for display
	 * Base implementation outputs plain text (CLI-friendly)
	 * MVC Application overrides this for HTML output
	 *
	 * @param \Throwable $Throwable
	 * @return string
	 */
	public function beautifyException( \Throwable $Throwable ): string
	{
		$Type = get_class( $Throwable );
		$Message = $Throwable->getMessage();
		$File = $Throwable->getFile();
		$Line = $Throwable->getLine();
		$Trace = $Throwable->getTraceAsString();

		$Output = "\n";
		$Output .= str_repeat( '=', 80 ) . "\n";
		$Output .= "APPLICATION ERROR\n";
		$Output .= str_repeat( '=', 80 ) . "\n\n";
		$Output .= "Type:    $Type\n";
		$Output .= "Message: $Message\n";
		$Output .= "File:    $File\n";
		$Output .= "Line:    $Line\n\n";
		$Output .= str_repeat( '-', 80 ) . "\n";
		$Output .= "Stack Trace:\n";
		$Output .= str_repeat( '-', 80 ) . "\n";
		$Output .= $Trace . "\n";
		$Output .= str_repeat( '=', 80 ) . "\n";

		return $Output;
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
		return $this->_Version;
	}

	/**
	 * Executes all initializer classes located in app/Initializers.
	 * @return void
	 */

	protected function executeInitializers(): void
	{
		Log\Log::debug( "executeInitializers()" );
		$Initializer = new InitializerRunner( $this );
		$Initializer->execute();
	}

	/**
	 * Loads event-listeners.yaml and maps all event listeners to their associated events.
	 * @return void
	 */

	public function initEvents(): void
	{
		Log\Log::debug( "initEvents()" );

		$EventLoader = new EventLoader( $this );
		$EventLoader->initEvents();
	}

	/**
	 * Call to run the application.
	 * @param array $Argv
	 * @return bool
	 * @throws Exception
	 */

	public function run( array $Argv = [] ): bool
	{
		$this->initErrorHandlers();

		$this->_Parameters = $Argv;

		if( !$this->onStart() )
		{
			Log\Log::fatal( "onStart() returned false. Aborting." );
			return false;
		}

		try
		{
			Log\Log::debug( "Running application v{$this->_Version}.." );
			$this->onRun();
		}
		catch( Exception $exception )
		{
			$Message = get_class( $exception ).', msg: '.$exception->getMessage();

			Log\Log::fatal( "Exception: $Message" );

			$this->onCrash(
				[
					'message' => $Message
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
		return $this->_Parameters;
	}

	/**
	 * Gets a parameter by name.
	 * @param string $name
	 * @return mixed
	 */

	public function getParameter( string $name ): mixed
	{
		return $this->_Parameters[ $name ];
	}

	/**
	 * @param string $name
	 * @param mixed $object
	 */

	public function setRegistryObject( string $name, mixed $object ): void
	{
		$this->_Registry->set( $name, $object );
	}

	/**
	 * @param string $name
	 * @return mixed
	 */

	public function getRegistryObject( string $name ) : mixed
	{
		return $this->_Registry->get( $name );
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
	 * @param ISettingSource|null $Source
	 * @return void
	 */

	protected function initSettings( ?ISettingSource $Source ): void
	{
		$DefaultBasePath = getenv( 'SYSTEM_BASE_PATH' ) ? : '.';
		$this->setBasePath( $DefaultBasePath );
		$Fallback = new Env( Data\Env::getInstance( "$DefaultBasePath/.env" ) );

		if( !$Source )
		{
			$this->_Settings = new SettingManager( $Fallback );
			Registry::getInstance()->set( 'Settings', $this->_Settings );
			return;
		}

		try
		{
			$this->_Settings = new SettingManager( $Source );

			$BasePath = $this->getSetting( 'system','base_path' ) ?? $DefaultBasePath;
			$Fallback = new Env( Data\Env::getInstance( "$BasePath/.env" ) );
			$this->_Settings->setFallback( $Fallback );
			$this->setBasePath( $BasePath );
		}
		catch( Exception $Exception )
		{
			$this->_Settings = new SettingManager( $Fallback );
		}

		Registry::getInstance()->set( 'Settings', $this->_Settings );
	}
}
