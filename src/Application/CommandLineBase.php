<?php

namespace Neuron\Application;

use Neuron\Log;

/**
 * Base functionality for command line applications.
 * Command line applications are designed to only be executed from the context
 * of the php-cli.
 * Allows for easy addition and handling of command line parameters.
 */

abstract class CommandLineBase extends Base
{
	private array $_handlers;

	/**
	 * Get the description of the application for --help.
	 * @return string
	 */

	protected abstract function getDescription(): string;

	/**
	 * Returns an array of all handlers for command line parameters.
	 * @return array
	 */

	protected function getHandlers(): array
	{
		return $this->_handlers;
	}

	/**
	 * Adds a handler for command line parameters.
	 * The switch is the parameter that causes the specified method to be called.
	 * If the param parameter is set to true, the token immediately following the
	 * switch on the command line will be passed as the parameter to the handler.
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
	 *
	 * @param string $switch the name of the switch.
	 * @param string $description the description of the switch.
	 * @param string $method the name of the switch handler method.
	 * @param bool|bool $param if true, the next parameter will be passed to the handler as the value of the switch.
	 */
	protected function addHandler( string $switch, string $description, string $method, bool $param = false ): void
	{
		$this->_handlers[ $switch ] = [
			'description'	=> $description,
			'method'			=> $method,
			'param'			=> $param
		];
	}

	/**
	 * Processes all parameters passed to the application.
	 *
	 * @return bool returns false if the execution should be halted.
	 */

	protected function processParameters(): bool
	{
		$paramCount = count( $this->getParameters() );

		for( $c = 0; $c < $paramCount; $c++ )
		{
			$param = $this->getParameters()[ $c ];

			if( !$this->handleParameter( $param, $c, $this->getParameters() ) )
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Handles a single parameter passed on the command line.
	 * @param string $param
	 * @param int $index
	 * @return bool returns false if the execution should be halted.
	 */

	private function handleParameter( string $param, int &$index ): bool
	{
		foreach( $this->getHandlers() as $switch => $info )
		{
			if( $switch != $param )
			{
				continue;
			}

			$method = $info[ 'method' ];

			if( $info[ 'param' ] )
			{
				$index++;
				$value = $this->getParameters()[ $index ];
				if( !$this->$method( $value ) )
				{
					return false;
				}

				continue;
			}

			return $this->$method();
		}

		return true;
	}

	/**
	 * Activated by the --help parameter. Shows all configured switches and their
	 * hints.
	 */

	protected function help(): bool
	{
		echo basename( $_SERVER['PHP_SELF'], '.php' )."\n";
		echo 'v'.$this->getVersion()."\n";
		echo $this->getDescription()."\n\n";
		echo "Switches:\n";
		$handlers = $this->getHandlers();
		ksort( $handlers );

		echo str_pad( 'Switch', 15 )."Value\n";
		echo str_pad( '------', 15 )."-----\n";

		foreach( $handlers as $switch => $info )
		{
			if( $info[ 'param' ] )
			{
				$value = str_pad( 'true', 5 );
			}
			else
			{
				$value = str_pad( ' ', 5 );
			}

			echo str_pad( $switch, 15 ).$value."$info[description]\n";
		}

		return false;
	}

	/**
	 * Called by ApplicationBase. Returning false terminates the application.
	 *
	 * @return bool
	 */

	protected function onStart() : bool
	{
		if( !$this->isCommandLine() )
		{
			Log\Log::fatal( "Application must be run from the command line." );
			return false;
		}

		$this->addHandler( '--help', 'Help', 'help' );

		if( !$this->processParameters() )
		{
			return false;
		}

		return parent::onStart();
	}
}
