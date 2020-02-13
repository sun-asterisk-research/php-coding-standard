# Sun* R&D PHP coding standard

[![Latest Stable Version](https://poser.pugx.org/sun-asterisk/coding-standard/v/stable)](https://packagist.org/packages/sun-asterisk/coding-standard)

Sun* coding standard for [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer).

## Installation

Using composer

```sh
composer require --dev sun-asterisk/coding-standard
```

## Usage

Add the `SunAsterisk` standard to your project's `phpcs.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<ruleset name="YourProject" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <rule ref="SunAsterisk"/>
</ruleset>
```

Or use it on the command line

```sh
vendor/bin/phpcs --standard=SunAsterisk <file or directory to check>
```

### Laravel

The `SunAsteriskLaravel` standard is extended for Laravel projects.
You can use it as below:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<ruleset name="YourProject" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <file>app</file>
    <file>config</file>
    <file>database</file>
    <file>resources</file>
    <file>routes</file>
    <file>tests</file>

    <exclude-pattern>vendor/</exclude-pattern>

    <rule ref="SunAsteriskLaravel"/>
</ruleset>
```

Refer to the [phpcs](https://github.com/squizlabs/PHP_CodeSniffer) documents for more detailed usage.
Also refer to [slevomat/coding-standard](https://github.com/slevomat/coding-standard) for details on some sniffs.
