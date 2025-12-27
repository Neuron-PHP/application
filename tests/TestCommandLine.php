<?php
namespace Tests;

class TestCommandLine extends \Neuron\Application\CommandLineBase
{
	public int $Interval = 60;
	protected function exitCommand(): bool
	{
		return false;
	}

	protected function pollCommand(): bool
	{
		return true;
	}

	/**
	 * Command line parameter to set the interval between polls.
	 * @param int $Interval interval in seconds.
	 * @return bool
	 */
	protected function intervalCommand( int $Interval ): bool
	{
		$this->Interval = $Interval;

		return true;
	}

	protected function failCommand( string $value ): bool
	{
		// Handler that returns false when given a parameter
		return false;
	}


	protected function onStart(): bool
	{
		$this->addHandler( '--exit', 'Test single execution command.', 'exitCommand' );
		$this->addHandler( '--poll', 'Performs a single poll and executes all ready jobs.', 'pollCommand' );
		$this->addHandler( '--interval', 'Set the interval between polls in seconds.', 'intervalCommand', true );
		$this->addHandler( '--fail', 'Test handler that fails with parameter.', 'failCommand', true );

		return parent::onStart();
	}


	/**
	 * @inheritDoc
	 */
	protected function onRun(): void
	{
	}

	/**
	 * @inheritDoc
	 */
	protected function getDescription(): string
	{
		return 'Test command line application.';
	}
}
