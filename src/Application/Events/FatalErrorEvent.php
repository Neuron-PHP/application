<?php

namespace Neuron\Application\Events;

use Neuron\Events\IEvent;

/**
 * Event fired when a fatal PHP error occurs.
 *
 * This event is triggered from the application's fatal error handler
 * when a fatal error is detected (E_ERROR, E_PARSE, E_CORE_ERROR, etc.).
 *
 * Use cases:
 * - Send critical alerts immediately to on-call personnel
 * - Log fatal errors to external services for post-mortem analysis
 * - Trigger automatic incident creation in ticketing systems
 * - Record error details for debugging and reproduction
 * - Track fatal error trends to identify stability issues
 *
 * @package Neuron\Application\Events
 */
class FatalErrorEvent implements IEvent
{
	/**
	 * @param string $type Error type name (e.g., 'Fatal Error', 'Parse Error')
	 * @param string $message Error message
	 * @param string $file File where error occurred
	 * @param int $line Line number where error occurred
	 */
	public function __construct(
		public readonly string $type,
		public readonly string $message,
		public readonly string $file,
		public readonly int $line
	)
	{
	}

	public function getName(): string
	{
		return 'application.fatal_error';
	}
}
