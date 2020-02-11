# Sun* R&D PHP coding standard

[![Latest Stable Version](https://poser.pugx.org/sun-asterisk/coding-standard/v/stable)](https://packagist.org/packages/sun-asterisk/coding-standard)

## Installation

Using composer

```sh
composer require --dev sun-asterisk/coding-standard
```

## Using

Add the standard to your project's `phpcs.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<ruleset name="YourProject" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <rule ref="SunAsterisk"/>
</ruleset>
```

A standard for Laravel projects is also included. You can use the standard as below

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
