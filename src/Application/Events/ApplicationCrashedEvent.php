<?php

namespace Neuron\Application\Events;

use Neuron\Events\IEvent;

/**
 * Event fired when the application crashes due to a fatal error or uncaught exception.
 *
 * This event is triggered from the application's error handlers (fatalHandler,
 * globalExceptionHandler, or onCrash method) when an unrecoverable error occurs.
 *
 * Use cases:
 * - Send critical alerts to monitoring services (Sentry, Rollbar, etc.)
 * - Log detailed error information to external logging services
 * - Notify administrators via email/SMS of critical failures
 * - Trigger emergency cleanup or failover procedures
 * - Track crash frequency and patterns for stability analysis
 *
 * @package Neuron\Application\Events
 */
class ApplicationCrashedEvent implements IEvent
{
	/**
	 * @param array $error Error details containing: type, message, file, line, and optionally trace
	 */
	public function __construct( public readonly array $error )
	{
	}

	public function getName(): string
	{
		return 'application.crashed';
	}
}
