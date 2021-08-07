<h1 align="center">
    <a href="https://josemmo.github.io/einvoicing/"><img src="docs/logo.svg" width="100" alt=""><br>European Invoicing (eInvoicing)</a>
</h1>

<p align="center">
    <a href="https://github.com/josemmo/einvoicing/actions"><img src="https://github.com/josemmo/einvoicing/workflows/CI/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/josemmo/einvoicing"><img src="https://img.shields.io/packagist/v/josemmo/einvoicing" alt="Latest Version"></a>
    <a href="#installation"><img src="https://img.shields.io/packagist/php-v/josemmo/einvoicing" alt="Supported PHP Versions"></a>
    <a href="LICENSE"><img src="https://img.shields.io/github/license/josemmo/einvoicing" alt="License"></a>
    <a href="https://josemmo.github.io/einvoicing/"><img src="https://img.shields.io/badge/online-docs-blueviolet" alt="Documentation"></a>
</p>

<p align="center">
    eInvoicing is a PHP library for creating and reading electronic invoices according to the <a href="https://ec.europa.eu/cefdigital/wiki/display/CEFDIGITAL/eInvoicing">eInvoicing Directive and European standard</a>.<br>
    It aims to be 100% compliant with <a href="https://ec.europa.eu/cefdigital/wiki/x/kwFVBg">EN 16931</a> as well as with the most popular CIUS and extensions, such as <a href="https://docs.peppol.eu/poacc/billing/3.0/bis/">PEPPOL BIS</a>.
</p>

## Installation
First of all, make sure your environment meets the following requirements:

- PHP 7.1 or higher
- [SimpleXML extension](https://www.php.net/book.simplexml) for reading and exporting UBL/CII invoices

Then, you should be able to install this library using Composer:

```
composer require josemmo/einvoicing
```

## Usage
For a proper quick start guide, visit the documentation website at
[https://josemmo.github.io/einvoicing/](https://josemmo.github.io/einvoicing/).

### Importing invoice documents
```php
use Einvoicing\Exceptions\ValidationException;
use Einvoicing\Readers\UblReader;

$reader = new UblReader();
$document = file_get_contents(__DIR__ . "/example.xml");
$inv = $reader->import($document);
try {
    $inv->validate();
} catch (ValidationException $e) {
    // Invoice is not EN 16931 complaint 
}
```

### Exporting invoice documents
```php
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\Party;
use Einvoicing\Presets;
use Einvoicing\Writers\UblWriter;

// Create PEPPOL invoice instance
$inv = new Invoice(Presets\Peppol::class);
$inv->setNumber('F-202000012')
    ->setIssueDate(new DateTime('2020-11-01'))
    ->setDueDate(new DateTime('2020-11-30'));

// Set seller
$seller = new Party();
$seller->setElectronicAddress(new Identifier('9482348239847239874', '0088'))
    ->setCompanyId(new Identifier('AH88726', '0183'))
    ->setName('Seller Name Ltd.')
    ->setTradingName('Seller Name')
    ->setVatNumber('ESA00000000')
    ->setAddress(['Fake Street 123', 'Apartment Block 2B'])
    ->setCity('Springfield')
    ->setCountry('DE');
$inv->setSeller($seller);

// Set buyer
$buyer = new Party();
$buyer->setElectronicAddress(new Identifier('ES12345', '0002'))
    ->setName('Buyer Name Ltd.')
    ->setCountry('FR');
$inv->setBuyer($buyer);

// Add a product line
$line = new InvoiceLine();
$line->setName('Product Name')
    ->setPrice(100)
    ->setVatRate(16)
    ->setQuantity(1);
$inv->addLine($line);

// Export invoice to a UBL document
header('Content-Type: text/xml');
$writer = new UblWriter();
echo $writer->export($inv);
```

## Roadmap
These are the expected features for the library and how's it going so far:

- [x] Representation of invoices, parties and invoice lines as objects
- [x] Compatibility with the most used [CIUS and extensions](https://ec.europa.eu/cefdigital/wiki/x/5xLoAg)
- [x] Export invoices to UBL documents
- [x] Import invoices from UBL documents
- [ ] Export invoices to CII documents
- [ ] Import invoices from CII documents
- [x] Proper documentation
