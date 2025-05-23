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
	private ?EventEmitter $_Emitter = null;

	/**
	 * @return EventEmitter|null
	 */

	public function getEmitter() : ?EventEmitter
	{
		return $this->_Emitter;
	}

	/**
	 * @return void
	 */

	public function initIfNeeded(): void
	{
		if( !$this->_Emitter )
		{
			$this->_Emitter = new EventEmitter();
			$this->serialize();
		}
	}

	/**
	 * @param IBroadcaster $Broadcaster
	 * @return void
	 */

	public static function registerBroadcaster( IBroadcaster $Broadcaster ) : void
	{
		$Emitter = self::getInstance();
		$Emitter->initIfNeeded();

		$Emitter->getEmitter()->registerBroadcaster( $Broadcaster );
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
