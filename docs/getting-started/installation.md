# Getting Started
eInvoicing (short for "European Invoicing") is a free and open-source library written in PHP for creating and reading
electronic invoices compliant with [EN 16931](https://ec.europa.eu/digital-building-blocks/sites/x/HYPXGw).

## Requirements
In order to install this library, your environment has to meet the following requirements:

- PHP 7.1 or higher
- [SimpleXML extension](https://www.php.net/book.simplexml) for reading and exporting UBL/CII invoices

## Installation
eInvoicing is distributed as a Composer package publicly available through Packagist, so installing it is as simple
as adding it as a dependency to your project:

```bash
composer require josemmo/einvoicing
```
