# CLI Licence checker for composer dependencies
This library offers a simple CLI tool to show the licenses used by composer dependencies in your project.
These licenses can be be verified against a list of allowed licenses to offer a way for your continuous integration
pipeline to block merging when a non-verified license is being introduced to the codebase.

## Installation
Installing should be a breeze thanks to `composer`:

```
composer require madewithlove/license-checker
```

## Configuration
To configure a list of allowed licenses, simply create an `.allowed-licences` file in the root of your project (where `composer.json` is located).
The file could look like this:
```
# contents of .allowed-licenses
MIT
BSD-3-Clause
```

## Usage
These are the different CLI commands

### List used licenses
```
vendor/madewithlove/license-checker-php/bin/licensed licenses:used
```

### List allowed licenses
```
vendor/madewithlove/license-checker-php/bin/licensed licenses:allowed
```

### Check licenses
```
vendor/madewithlove/license-checker-php/bin/licensed licenses:check
```
