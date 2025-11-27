<?php

namespace Neuron\Application\Events;

use Neuron\Events\IEvent;

/**
 * Event fired when the application is shutting down gracefully.
 *
 * This event is triggered before the application terminates, allowing
 * listeners to perform cleanup operations, save state, or close connections.
 *
 * Use cases:
 * - Close database connections
 * - Save application state or cache data
 * - Release file locks or resources
 * - Send final metrics or analytics data
 * - Log shutdown information for audit trails
 * - Notify external services of application shutdown
 *
 * @package Neuron\Application\Events
 */
class ApplicationShuttingDownEvent implements IEvent
{
	public function __construct()
	{
	}

	public function getName(): string
	{
		return 'application.shutting_down';
	}
}
