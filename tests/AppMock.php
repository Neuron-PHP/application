<?php
namespace Tests;

use Exception;
use Neuron\Application\Base;
use Neuron\Application\CrossCutting\Event;

class AppMock extends Base
{
	public bool $Crash    = false;
	public bool $DidCrash = false;
	public bool $Error    = false;
	public bool $DidError = true;
	public bool $FailStart = false;

	protected function onRun() : void
	{
		if( $this->Error )
		{
			$Test = $Bogus[ 'test' ];
		}

		if( $this->Crash )
		{
			throw new Exception( 'Mock failure.' );
		}

		Event::emit( new TestEvent() );
	}

	protected function onStart() : bool
	{
		if( $this->FailStart )
		{
			return false;
		}

		return parent::onStart();
	}

	protected function onCrash( array $Error ): void
	{
		$this->DidCrash = true;

		parent::onCrash( $Error );
	}

	public function crash(): void
	{
		// Simulate a crash by calling onCrash directly with mock error details
		$this->onCrash([
			'type' => 'Mock Fatal Error',
			'message' => 'Simulated crash for testing',
			'file' => __FILE__,
			'line' => __LINE__
		]);
	}

	protected function onError( string $Message ) : bool
	{
		$this->DidError = true;

		parent::onError( $Message );

		return false;
	}

	// Public wrapper to test protected formatFatalError method
	public function formatFatalError( string $type, string $message, string $file, int $line ): string
	{
		return parent::formatFatalError( $type, $message, $file, $line );
	}
}
