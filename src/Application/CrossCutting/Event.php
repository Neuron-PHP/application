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
	 * @param array $registry
	 * @return void
	 */

	public static function registerListeners( array $registry ) : void
	{
		$emitter = self::getInstance();
		$emitter->initIfNeeded();

		$emitter->getEmitter()->registerListeners( $registry );
	}

	/**
	 * @param string $eventName
	 * @param string $listener
	 * @return void
	 */

	public static function registerListener( string $eventName, string $listener ) : void
	{
		$emitter = self::getInstance();
		$emitter->initIfNeeded();

		$emitter->getEmitter()->registerListener( $eventName, $listener );
	}

	/**
	 * @param $event
	 * @return void
	 */

	public static function emit( $event ) : void
	{
		$emitter = self::getInstance();
		$emitter->initIfNeeded();

		$emitter->getEmitter()->emit( $event );
	}
}
