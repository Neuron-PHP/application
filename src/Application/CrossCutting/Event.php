<?php


namespace Neuron\Application\CrossCutting;

use Neuron\Application\Facades\EventEmitter;
use Neuron\Events\Broadcasters\IBroadcaster;
use Neuron\Patterns\Singleton\Memory;

/**
 * Event Singleton to manage events as a cross-cutting concern.
 */

class Event extends Memory
{
	private ?EventEmitter $_emitter = null;

	/**
	 * @return EventEmitter|null
	 */

	public function getEmitter() : ?EventEmitter
	{
		return $this->_emitter;
	}

	/**
	 * @return void
	 */

	public function initIfNeeded(): void
	{
		if( !$this->_emitter )
		{
			$this->_emitter = new EventEmitter();
			$this->serialize();
		}
	}

	/**
	 * @param IBroadcaster $broadcaster
	 * @return void
	 */

	public static function registerBroadcaster( IBroadcaster $broadcaster ) : void
	{
		$emitter = self::getInstance();
		$emitter->initIfNeeded();

		$emitter->getEmitter()->registerBroadcaster( $broadcaster );
	}

	/**
	 * @param array $Registry
	 * @return void
	 */

	public static function registerListeners( array $Registry ) : void
	{
		$Emitter = self::getInstance();
		$Emitter->initIfNeeded();

		$Emitter->getEmitter()->registerListeners( $Registry );
	}

	/**
	 * @param string $EventName
	 * @param string $Listener
	 * @return void
	 */

	public static function registerListener( string $EventName, string $Listener ) : void
	{
		$Emitter = self::getInstance();
		$Emitter->initIfNeeded();

		$Emitter->getEmitter()->registerListener( $EventName, $Listener );
	}

	/**
	 * @param $Event
	 * @return void
	 */

	public static function emit( $Event ) : void
	{
		$Emitter = self::getInstance();
		$Emitter->initIfNeeded();

		$Emitter->getEmitter()->emit( $Event );
	}
}
