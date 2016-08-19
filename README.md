# PHP Process Library

Use this library to create multiprocessed parallel tasks for PHP CLI (command line interface oriented).

## Features

- Simple process signal handling
- Process management (create and recreate tasks on failure)
- Control the process tree (parent, child, session)

### Future

- Simplified command line interpreter
- Inter process communication (IPC)
- Easy integration with other frameworks

## Installing with composer

    composer require phcco/phpplib "~1.0"

## Examples

Check the `examples` folder.

## Requirements

Posix and PCNTL on PHP 5.6 or above.