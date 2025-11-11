<?php

namespace Neuron\Application;

use Neuron\Application\CrossCutting\Event;
use Neuron\Events\Broadcasters\Generic;
use Neuron\Log;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads and registers event listeners from the config/event-listeners.yaml file.
 */
class EventLoader
{
	private Base $_base;

	public function __construct( Base $base )
	{
		$this->_base = $base;
	}

	/**
	 * @return void
	 */

	public function initEvents(): void
	{
		Event::registerBroadcaster( new Generic() );

		$path = $this->getPath();

		if( !file_exists( $path . '/event-listeners.yaml' ) )
		{
			return;
		}

		try
		{
			$data = Yaml::parseFile( $path . '/event-listeners.yaml' );
		}
		catch( ParseException $exception )
		{
			Log\Log::error( "Failed to load event listeners: " . $exception->getMessage() );
			return;
		}

		$this->loadEvents( $data[ 'events' ] );
	}

	/**
	 * @return string
	 */

	protected function getPath(): string
	{
		$file = $this->_base->getBasePath() . '/config';

		if( $this->_base->getEventListenersPath() )
		{
			$file = $this->_base->getEventListenersPath();
		}
		return $file;
	}

	/**
	 * @param $events
	 * @return void
	 */

	protected function loadEvents( $events ): void
	{
		foreach( $events as $event )
		{
			foreach( $event[ 'listeners' ] as $listener )
			{
				Event::registerListener( $event[ 'class' ], $listener );
			}
		}
	}
}
