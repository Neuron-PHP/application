<?php

namespace Neuron\Application;

use Neuron\Application\CrossCutting\Event;
use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;
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
	private IFileSystem $fs;

	/**
	 * @param Base $base
	 * @param IFileSystem|null $fs File system implementation (null = use real file system)
	 */
	public function __construct( Base $base, ?IFileSystem $fs = null )
	{
		$this->_base = $base;
		$this->fs = $fs ?? new RealFileSystem();
	}

	/**
	 * Register the in-process broadcaster, if there is not one already, and map
	 * the listeners in event-listeners.yaml to their events.
	 *
	 * Safe to call more than once. A broadcaster is only skipped as a duplicate
	 * when it is the same object, and each listener is added to every
	 * broadcaster, so a second Generic here used to receive every listener
	 * again and each event reached each listener twice.
	 *
	 * @return void
	 */

	public function initEvents(): void
	{
		if( !self::hasInProcessBroadcaster() )
		{
			Event::registerBroadcaster( new Generic() );
		}

		$path = $this->getPath();
		$eventFile = $path . '/event-listeners.yaml';

		if( !$this->fs->fileExists( $eventFile ) )
		{
			return;
		}

		$content = $this->fs->readFile( $eventFile );

		if( $content === false )
		{
			Log\Log::error( "Failed to read event listeners file: $eventFile" );
			return;
		}

		try
		{
			$data = Yaml::parse( $content );
		}
		catch( ParseException $exception )
		{
			Log\Log::error( "Failed to parse event listeners: " . $exception->getMessage() );
			return;
		}

		$this->loadEvents( $data[ 'events' ] );
	}

	/**
	 * Whether a Generic broadcaster is registered already. Broadcasters of
	 * other kinds - ones that forward events elsewhere - are not counted: they
	 * sit alongside it.
	 *
	 * @return bool
	 */

	private static function hasInProcessBroadcaster(): bool
	{
		foreach( Event::getBroadcasters() as $broadcaster )
		{
			if( $broadcaster instanceof Generic )
			{
				return true;
			}
		}

		return false;
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
