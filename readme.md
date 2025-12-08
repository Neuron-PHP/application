[![CI](https://github.com/Neuron-PHP/application/actions/workflows/ci.yml/badge.svg)](https://github.com/Neuron-PHP/application/actions)
[![codecov](https://codecov.io/gh/Neuron-PHP/application/graph/badge.svg)](https://codecov.io/gh/Neuron-PHP/application)
# Neuron-PHP Application

A comprehensive application framework component for PHP 8.4+ that provides base classes, configuration management, event handling, and lifecycle management for building robust applications with the Neuron framework.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Features](#core-features)
- [Application Base Class](#application-base-class)
- [Configuration Management](#configuration-management)
- [Event System](#event-system)
- [Initializers](#initializers)
- [Command Line Applications](#command-line-applications)
- [Logging](#logging)
- [Error Handling](#error-handling)
- [Registry Pattern](#registry-pattern)
- [Testing](#testing)
- [Best Practices](#best-practices)
- [More Information](#more-information)

## Installation

### Requirements

- PHP 8.4 or higher
- Extensions: curl, json
- Composer

### Install via Composer

```bash
composer require neuron-php/application
```

## Quick Start

### Basic Application

```php
use Neuron\Application\Base;
use Neuron\Data\Settings\Source\Yaml;

class MyApplication extends Base
{
    protected function onStart(): bool
    {
        \Neuron\Log\Log::info('Application starting...');
        return true;  // Return false to abort
    }

    protected function onRun(): void
    {
        // Main application logic
        echo "Running application v" . $this->getVersion() . "\n";
    }

    protected function onStop(): void
    {
        \Neuron\Log\Log::info('Application stopped');
    }
}

// Bootstrap and run
$settings = new Yaml('config/neuron.yaml');
$app = new MyApplication('1.0.0', $settings);
$app->run();
```

## Core Features

- **Application Lifecycle Management**: onStart, onRun, onStop, onFinish hooks
- **Configuration System**: Flexible settings from YAML, INI, ENV sources
- **Event-Driven Architecture**: Global event emitter with listener configuration
- **Initializer System**: Automatic loading and execution of initialization code
- **Logging Integration**: Built-in logging with multiple destinations
- **Error Handling**: Comprehensive error and fatal error handlers
- **Registry Pattern**: Global object storage and retrieval
- **Command Line Support**: Specialized base class for CLI applications
- **Settings Fallback**: Automatic fallback to environment variables

## Application Base Class

The `Base` class provides core application functionality:

### Lifecycle Methods

```php
class MyApp extends Base
{
    /**
     * Called before the application starts
     * Return false to abort startup
     */
    protected function onStart(): bool
    {
        // Initialize resources
        $db = $this->initDatabase();
        $this->setRegistryObject('database', $db);

        // Load configuration
        if (!$this->loadConfiguration()) {
            Log::error('Configuration failed');
            return false;  // Abort startup
        }

        return true;
    }

    /**
     * Main application logic
     */
    protected function onRun(): void
    {
        // Process requests, run main loop, etc.
        $this->processRequests();
    }

    /**
     * Called when application is stopping
     */
    protected function onStop(): void
    {
        // Cleanup resources
        $this->closeConnections();
    }

    /**
     * Called after everything else
     */
    protected function onFinish(): void
    {
        Log::info('Application finished');
    }

    /**
     * Handle errors
     */
    protected function onError($level, $message, $file, $line): void
    {
        Log::error("Error: $message in $file:$line");
    }

    /**
     * Handle fatal errors
     */
    protected function onFatal(): void
    {
        $error = error_get_last();
        Log::fatal('Fatal error: ' . $error['message']);
        $this->setCrashed(true);
    }
}
```

### Running the Application

```php
// Create and configure
$app = new MyApp('1.0.0', new Yaml('neuron.yaml'));

// Set parameters (e.g., from command line)
$app->setParameters($_SERVER['argv']);

// Run with optional parameters
$app->run(['--verbose', '--mode=production']);
```

## Configuration Management

### Configuration Sources

The application supports multiple configuration sources through the `ISettingSource` interface:

```php
use Neuron\Data\Settings\Source\Yaml;
use Neuron\Data\Settings\Source\Ini;
use Neuron\Data\Settings\Source\Env;

// YAML configuration
$yamlSource = new Yaml('config/app.yaml');
$app = new MyApp('1.0.0', $yamlSource);

// INI configuration
$iniSource = new Ini('config/app.ini');
$app = new MyApp('1.0.0', $iniSource);

// Environment variables (fallback)
$envSource = new Env();
$app = new MyApp('1.0.0', $envSource);

// No configuration (defaults to environment)
$app = new MyApp('1.0.0');
```

### Configuration Structure

Example `neuron.yaml`:

```yaml
system:
  timezone: America/New_York
  base_path: /app
  environment: production

logging:
  destination: \Neuron\Log\Destination\File
  format: \Neuron\Log\Format\PlainText
  file: app.log
  level: info

events:
  listeners_path: app/Listeners

database:
  host: localhost
  port: 3306
  name: myapp
  username: dbuser
  password: secret

cache:
  enabled: true
  driver: redis
  ttl: 3600
```

### Accessing Settings

```php
class MyApp extends Base
{
    protected function onStart(): bool
    {
        // Get settings
        $dbHost = $this->getSetting('database', 'host');
        $cacheEnabled = $this->getSetting('cache', 'enabled');

        // Set runtime settings
        $this->setSetting('app', 'mode', 'maintenance');

        // Get the SettingManager instance
        $settings = $this->getSettingManager();
        if ($settings) {
            $source = $settings->getSource();
            // Work with the source directly
        }

        return true;
    }
}
```

### Settings with Fallback

```php
// Configuration with environment fallback
$yamlSource = new Yaml('neuron.yaml');
$envFallback = new Env();

$settings = new SettingManager($yamlSource);
$settings->setFallback($envFallback);

$app = new MyApp('1.0.0', $settings);

// Now settings check YAML first, then environment variables
$apiKey = $app->getSetting('api', 'key');  // Checks neuron.yaml then API_KEY env var
```

## Event System

### Global Event Emitter

The application provides a global event emitter through the `CrossCutting\Event` class:

```php
use Neuron\Application\CrossCutting\Event;

// Emit events from anywhere
Event::emit(new UserRegisteredEvent($user));

// In your application
class MyApp extends Base
{
    protected function onStart(): bool
    {
        // Initialize event system
        $this->initEvents();

        // Add listeners programmatically
        Event::addListener(
            UserRegisteredEvent::class,
            new WelcomeEmailListener()
        );

        return true;
    }
}
```

### Event Configuration

Configure event listeners via YAML (`event-listeners.yaml`):

```yaml
listeners:
  UserRegisteredEvent:
    - App\Listeners\SendWelcomeEmail
    - App\Listeners\UpdateAnalytics
    - App\Listeners\NotifyAdmins

  OrderCompletedEvent:
    - App\Listeners\UpdateInventory
    - App\Listeners\SendInvoice
    - App\Listeners\ProcessCommission
```

## Initializers

Initializers are classes that run during application startup for bootstrapping:

### Creating an Initializer

Create files in `app/Initializers/`:

```php
// app/Initializers/DatabaseInitializer.php
namespace App\Initializers;

use Neuron\Patterns\IRunnable;

class DatabaseInitializer implements IRunnable
{
    public function run(): void
    {
        // Initialize database connections
        $db = new DatabaseConnection(
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        );

        $this->setRegistryObject('database', $db);
    }
}
```

### Running Initializers

Initializers are automatically loaded and executed during `onStart()`:

They are atomic instances of classes implementing `IRunnable` and enable modular startup logic.

```php
class InitTest implements IRunnable
{
	public function run( array $Argv = [] ): mixed
	{
		Registry::getInstance()
				  ->set( 'examples\Initializers\InitTest', 'Hello World!' );

		return true;
	}
}
```

## Command Line Applications

The `CommandLineBase` class extends `Base` with CLI-specific features:

```php
use Neuron\Application\CommandLineBase;

class CliApp extends CommandLineBase
{
    protected function onRun(): void
    {
        $args = $this->getParameters();

        // Parse command line arguments
        $command = $args[1] ?? 'help';

        switch ($command) {
            case 'process':
                $this->processData();
                break;
            case 'import':
                $this->importData();
                break;
            default:
                $this->showHelp();
        }
    }

    private function showHelp(): void
    {
        echo "Usage: php app.php [command]\n";
        echo "Commands:\n";
        echo "  process  - Process data\n";
        echo "  import   - Import data\n";
    }
}

// Run CLI app
$app = new CliApp('1.0.0');
$app->setParameters($argv);
$app->run();
```

## Logging

### Built-in Logging

The application automatically initializes logging based on configuration:

```php
use Neuron\Log\Log;

class MyApp extends Base
{
    protected function onStart(): bool
    {
        // Logging is already initialized from config
        // Access the Log singleton directly
        Log::info('Application starting');
        Log::debug('Debug message');
        Log::warning('Warning message');
        Log::error('Error occurred');

        return true;
    }
}
```

## Error Handling

### Error Handlers

```php
class MyApp extends Base
{
    public function __construct($version, $source = null)
    {
        parent::__construct($version, $source);

        // Enable error handling
        $this->_HandleErrors = true;
        $this->_HandleFatal = true;
    }

    protected function onError($level, $message, $file, $line): void
    {
        // Custom error handling
        Log::error("Error [$level]: $message in $file:$line");

        // Send alert for critical errors
        if ($level === E_ERROR) {
            $this->sendAlert("Critical error: $message");
        }
    }

    protected function onFatal(): void
    {
        $error = error_get_last();

        // Log fatal error
        \Neuron\Log\Log::fatal('Fatal: ' . $error['message']);

        // Set crashed state
        $this->setCrashed(true);

        // Cleanup before exit
        $this->emergencyCleanup();
    }
}
```

### Crash Detection

```php
$app = new MyApp('1.0.0');
$app->run();

if ($app->getCrashed()) {
    // Handle crash recovery
    file_put_contents('crash.log', date('Y-m-d H:i:s') . ' - Application crashed' . PHP_EOL, FILE_APPEND);

    // Restart or alert
    exec('php restart.php');
}
```

## Registry Pattern

### Using the Registry

```php
class MyApp extends Base
{
    protected function onStart(): bool
    {
        // Store objects in registry
        $this->setRegistryObject('database', $dbConnection);
        $this->setRegistryObject('cache', $cacheManager);
        $this->setRegistryObject('api.client', $apiClient);

        // Retrieve objects
        $db = $this->getRegistryObject('database');
        $cache = $this->getRegistryObject('cache');

        // Direct registry access
        $registry = Registry::getInstance();
        $registry->set('app.mode', 'production');
        $mode = $registry->get('app.mode');

        return true;
    }
}
```

### Registry Best Practices

```php
// Use namespaced keys
$app->setRegistryObject('services.email', $emailService);
$app->setRegistryObject('services.payment', $paymentService);
$app->setRegistryObject('repositories.user', $userRepo);

// Store configurations
$app->setRegistryObject('config.api.keys', $apiKeys);
$app->setRegistryObject('config.features', $featureFlags);

// Store runtime state
$app->setRegistryObject('runtime.start_time', microtime(true));
$app->setRegistryObject('runtime.request_count', 0);
```

## Testing

### Testing Applications

```php
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testApplicationStartup(): void
    {
        $settings = new Memory();
        $settings->set('system', 'timezone', 'UTC');

        $app = new MyApp('1.0.0', $settings);

        // Test startup
        $reflection = new ReflectionMethod($app, 'onStart');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($app);
        $this->assertTrue($result);
    }

    public function testErrorHandling(): void
    {
        $app = new MyApp('1.0.0');
        $app->enableErrorHandling(true);

        // Trigger error
        $reflection = new ReflectionMethod($app, 'onError');
        $reflection->setAccessible(true);

        $reflection->invoke($app, E_WARNING, 'Test error', 'test.php', 100);

        // Assert error was logged
        $this->assertStringContainsString('Test error', $this->getLogContent());
    }
}
```

### Mocking Applications

```php
class MockApplication extends Base
{
    public bool $started = false;
    public bool $ran = false;
    public bool $stopped = false;

    protected function onStart(): bool
    {
        $this->started = true;
        return true;
    }

    protected function onRun(): void
    {
        $this->ran = true;
    }

    protected function onStop(): void
    {
        $this->stopped = true;
    }
}

// Test lifecycle
$app = new MockApplication('1.0.0');
$app->run();

$this->assertTrue($app->started);
$this->assertTrue($app->ran);
$this->assertTrue($app->stopped);
```

## Best Practices

### Application Structure

```php
class ProductionApp extends Base
{
    protected function onStart(): bool
    {
        // 1. Initialize critical services first
        if (!$this->initializeDatabase()) {
            return false;
        }

        // 2. Load configuration
        $this->loadConfiguration();

        // 3. Set up logging
        $this->setupLogging();

        // 4. Initialize events
        $this->initEvents();

        // 5. Run initializers
        $this->executeInitializers();

        // 6. Validate environment
        if (!$this->validateEnvironment()) {
            $this->log('Environment validation failed', 'error');
            return false;
        }

        return true;
    }

    private function validateEnvironment(): bool
    {
        // Check required settings
        $required = ['database.host', 'api.key', 'cache.driver'];

        foreach ($required as $setting) {
            [$section, $key] = explode('.', $setting);
            if (!$this->getSetting($section, $key)) {
                \Neuron\Log\Log::error("Missing required setting: $setting");
                return false;
            }
        }

        return true;
    }
}
```


## Advanced Features

### Application Versioning

```php
use Neuron\Data\Objects\Version;

// Load version from file
$version = new Version();
$version->loadFromFile('.version.json');

$app = new MyApp($version->getAsString());

// Access version in app
echo "Running version: " . $app->getVersion();
```


## More Information

- **Neuron Framework**: [neuronphp.com](http://neuronphp.com)
- **GitHub**: [github.com/neuron-php/application](https://github.com/neuron-php/application)
- **Packagist**: [packagist.org/packages/neuron-php/application](https://packagist.org/packages/neuron-php/application)
