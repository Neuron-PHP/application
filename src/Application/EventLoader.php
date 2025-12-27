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
	 * @return void
	 */

	public function initEvents(): void
	{
		Event::registerBroadcaster( new Generic() );

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
