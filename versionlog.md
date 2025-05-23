## 0.6.33 2025-05-23
* Added the Crashed state.

## 0.6.32 2025-05-21

## 0.6.31

## 0.6.30 2025-02-06
* Transitioned from core package to application.

## 0.6.29 2025-01-27
* Updated data to 0.7

## 0.6.28 2025-01-16
* Fix for registry.

## 0.6.27 2025-01-16
* Added settings to the registry.

## 0.6.26 2025-01-16
* Refactored how settings are handled. If no setting file is found or supplied, the application default to environment variables.
* Added additional tests.

## 0.6.25
## 0.6.24
## 0.6.23
## 0.6.22
## 0.6.21
* Refactored application base.

## 0.6.20 2024-01-10
* Added the CommandLineBase test.
* Added the ability to for switch commands to gracefully halt execution by returning false.

## 0.6.19
## 0.6.18 2025-01-09
* Refactored the application base.

## 0.6.17
* Added an exit after help.

## 0.6.16
* Added description to help.

## 0.6.15 2024-01-05
* Moved base path to core.

## 0.6.14 2024-12-24
* Fixed a case sensitivity issue in initializers.

## 0.6.13 2024-12-18
* Updated the setting for event listeners.'

## 0.6.12 2024-12-17
* Added base_path to the log path.

## 0.6.11 2024-12-16
* Handling of no event-listeners.yaml present.

## 0.6.10
* Fixed an issue with blank event listeners path in config.ini

## 0.6.9 2024-12-16
* Added the ability to map event listeners to events using the new event-listeners.yaml file.

## 0.6.8 2024-12-15
* Added base_path

## 0.6.7
* Added a log in application crash.

## 0.6.6 2024-12-13
* Fixed the travis build.

## 0.6.5 2024-12-13
* Added a timezone setting to the application config.
* Added execution of all classes in the initializers directory.

## 0.6.4 2024-11-27
* Fixed travis build.

## 0.6.3 2024-11-27
* Applications now use cross cutting log functionality.
* Applications now can process configuration files.
* Updated data component version.

## 0.6.2 2024-10-16
* Updated logging to 0.7

## 0.6.1

## 0.5.8 2022-04-04
* Scheduled release

## 0.5.7 2022-03-31
* Updated Events and Logger versions.
* Added error and shutdown handlers.
* Added CrossCutting\Event
* Expanded application interface to include initializers.

## 0.5.6 2020-08-26
* Removed filters facade.

## 0.5.5 2020-08-26
* Renamed Facades\Filter to Filters.
* Renamed Facades\Event to EventEmitter

## 0.5.4

## 0.5.3
* Added Facades\Event
* Added Facades\Filter

## 0.5.2 2020-08-20
* Added Neuron\ExceptionBase

## 0.5.1 2020-08-20
* Refactoring.

## 0.5.0 2020-08-19
