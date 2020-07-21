# European Invoicing (eInvoicing)
[![Build Status](https://travis-ci.com/josemmo/einvoicing.svg?branch=master)](https://travis-ci.com/josemmo/einvoicing)
[![Latest Version](https://poser.pugx.org/josemmo/einvoicing/version)](https://packagist.org/packages/josemmo/einvoicing)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/josemmo/einvoicing)](#installation)
[![License](https://poser.pugx.org/josemmo/einvoicing/license)](LICENSE)

eInvoicing is a PHP library for creating and reading electronic invoices according to the [eInvoicing Directive and European standard](https://ec.europa.eu/cefdigital/wiki/display/CEFDIGITAL/eInvoicing).

It aims to be 100% compliant with [EN 16931](https://ec.europa.eu/cefdigital/wiki/x/kwFVBg) as well as with the most popular CIUS and extensions, such as [PEPPOL](https://peppol.eu/).

> ⚠️ WARNING: This library is under heavy development and is not ready for production yet. ⚠️

## Installation
First of all, make sure your environment meets the following requirements:

- PHP 7.1 or higher
- [SimpleXML extension](https://www.php.net/book.simplexml) for reading and exporting UBL/CII invoices
- [OpenSSL extension](https://www.php.net/book.openssl) for signing and verifying invoices

Then, you should be able to install this library using Composer:

```
composer require josemmo/einvoicing
```

## Usage
I intend to create a complete manual with examples and a Quick Start guide once the project gets closer to release.
For now, here's the [phpDoc documentation](https://josemmo.github.io/einvoicing/).

## Checklist
These are the expected features for the library and how's it going so far:

- [x] Representation of invoices, parties and invoice lines as objects
- [ ] Export invoices to UBL/CII documents
- [ ] Import invoices from UBL/CII documents
- [ ] Compatibility with the most used [CIUS and extensions](https://ec.europa.eu/cefdigital/wiki/x/5xLoAg)
- [ ] Proper documentation