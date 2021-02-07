# European Invoicing (eInvoicing)
[![Build Status](https://github.com/josemmo/einvoicing/workflows/CI/badge.svg)](https://github.com/josemmo/einvoicing/actions)
[![Latest Version](https://img.shields.io/packagist/v/josemmo/einvoicing?include_prereleases)](https://packagist.org/packages/josemmo/einvoicing)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/josemmo/einvoicing)](#installation)
[![License](https://img.shields.io/github/license/josemmo/einvoicing)](LICENSE)
[![Documentation](https://img.shields.io/badge/online-docs-blueviolet)](https://josemmo.github.io/einvoicing/)

eInvoicing is a PHP library for creating and reading electronic invoices according to the [eInvoicing Directive and European standard](https://ec.europa.eu/cefdigital/wiki/display/CEFDIGITAL/eInvoicing).

It aims to be 100% compliant with [EN 16931](https://ec.europa.eu/cefdigital/wiki/x/kwFVBg) as well as with the most popular CIUS and extensions, such as [PEPPOL BIS](https://docs.peppol.eu/poacc/billing/3.0/bis/).

> ⚠️ WARNING: This library is almost ready for production. Some features may not be available yet. ⚠️

## Installation
First of all, make sure your environment meets the following requirements:

- PHP 7.1 or higher
- [SimpleXML extension](https://www.php.net/book.simplexml) for reading and exporting UBL/CII invoices

Then, you should be able to install this library using Composer:

```
composer require josemmo/einvoicing
```

## Usage
For a quick guide on how to get started, visit the documentation website at
[https://josemmo.github.io/einvoicing/](https://josemmo.github.io/einvoicing/).

## Roadmap
These are the expected features for the library and how's it going so far:

- [x] Representation of invoices, parties and invoice lines as objects
- [x] Compatibility with the most used [CIUS and extensions](https://ec.europa.eu/cefdigital/wiki/x/5xLoAg)
- [x] Export invoices to UBL documents
- [x] Import invoices from UBL documents
- [ ] Export invoices to CII documents
- [ ] Import invoices from CII documents
- [x] Proper documentation
