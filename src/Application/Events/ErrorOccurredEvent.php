<?php

namespace Neuron\Application\Events;

use Neuron\Events\IEvent;

/**
 * Event fired when a PHP error occurs (non-fatal).
 *
 * This event is triggered from the application's error handler for
 * non-fatal PHP errors like notices, warnings, and user-triggered errors.
 *
 * Use cases:
 * - Track error frequency and patterns
 * - Send non-critical errors to monitoring services
 * - Log detailed error context for debugging
 * - Trigger alerts when error rates exceed thresholds
 * - Generate error reports for developers
 *
 * @package Neuron\Application\Events
 */
class ErrorOccurredEvent implements IEvent
{
	/**
	 * @param int $errorNo PHP error number (E_NOTICE, E_WARNING, etc.)
	 * @param string $message Error message
	 * @param string $file File where error occurred
	 * @param int $line Line number where error occurred
	 */
	public function __construct(
		public readonly int $errorNo,
		public readonly string $message,
		public readonly string $file,
		public readonly int $line
	)
	{
	}

	public function getName(): string
	{
		return 'application.error';
	}
}
