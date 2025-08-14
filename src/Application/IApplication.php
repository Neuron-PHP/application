<?php

namespace Neuron\Application;

use Neuron\Patterns;

/**
 * Interface IApplication
 */
interface IApplication extends Patterns\IRunnable
{
	/**
	 * @param string $section
	 * @param string $name
	 * @return mixed
	 */

	public function getSetting( string $section, string $name ) : mixed;

	/**
	 * @param string $section
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setSetting( string $section, string $name, string $value): void;

	/**
	 * @param string $name
	 * @param mixed $object
	 * @return mixed
	 */

	public function setRegistryObject( string $name, mixed $object );

	/**
	 * @param string $name
	 * @return mixed
	 */

	public function getRegistryObject( string $name ) : mixed;
}
