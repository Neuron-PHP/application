<?php
namespace Neuron\Application\Facades;

use Neuron\Events\Broadcasters\IBroadcaster;
use Neuron\Events\Emitter;

/**
 * Facade to simplify the registration of events and listeners and the emitting of events.
 */
class EventEmitter
{
	private Emitter $_emitter;

	/**
	 *
	 */
	public function __construct( )
	{
		$this->_emitter = new Emitter();
	}

	/**
	 * Registers a new broadcaster.
	 * @param IBroadcaster $broadcaster
	 * @return void
	 */

	public function registerBroadcaster( IBroadcaster $broadcaster ) : void
	{
		$this->_emitter->registerBroadcaster( $broadcaster );
	}

	/**
	 * Maps an array of events to an array of listeners.
	 * Listeners can either be an object or a class name
	 * to be instantiated when the event is fired.
	 *
	 * @param array $registry
	 */

	public function registerListeners( array $registry ) : void
	{
		$broadcasters = $this->_emitter->getBroadcasters();

		foreach( $broadcasters as $broadcaster )
		{
			foreach( $registry as $class => $listeners )
			{
				foreach( $listeners as $listener )
				{
					$broadcaster->addListener( $class, $listener );
				}
			}
		}
	}

	/**
	 * Registers a listener to an event.
	 * @param string $eventName
	 * @param string $listener
	 */

	public function registerListener( string $eventName, string $listener ) : void
	{
		$broadcasters = $this->_emitter->getBroadcasters();

		foreach( $broadcasters as $broadcaster )
		{
			$broadcaster->addListener( $eventName, $listener );
		}
	}

	/**
	 * Emits an event across all broadcasters to all registered
	 * listeners.
	 * @param $event
	 */

	public function emit( $event ) : void
	{
		$this->_emitter->emit( $event );
	}
}
