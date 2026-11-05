# Importing, exporting and validating documents
To switch between [`Invoice`](../reference/invoice.md) instances and UBL/CII documents, this library provides a set of
readers and writers for importing and exporting invoices, respectively.
For example, if you want to export an invoice to UBL, you'll have to use the [`UblWriter`](../reference/ubl-writer.md).

## Exporting invoices
Once you have a working invoice instance, create a writer to export it to a document. For example:
```php
use Einvoicing\Writers\UblWriter;

$writer = new UblWriter();
$document = $writer->export($inv);
file_put_contents(__DIR__ . "/example.xml", $document);
```

### Sending documents over Peppol
This library creates and reads documents, but it does not deliver them. To send an exported UBL invoice over the
Peppol network you need a Peppol access point. Free access points run by non-profits and governments exist, such as
[Let's Peppol](https://letspeppol.org/), and OpenPeppol publishes the
[list of certified service providers](https://peppol.org/members/peppol-certified-service-providers/).

Some PHP packages send the exported UBL to an access point, for example
[`getpeppr/getpeppr-php`](https://github.com/zerolooplabs/getpeppr-php) or
[`tigron/peppyrus-api-php`](https://github.com/tigron/peppyrus-api-php).

## Importing documents
Similar to exporting invoices, you need a reader to import a document into an invoice instance:
```php
use Einvoicing\Readers\UblReader;

$reader = new UblReader();
$document = file_get_contents(__DIR__ . "/example.xml");
try {
    $inv = $reader->import($document);
} catch (\InvalidArgumentException $e) {
    // Failed to parse XML document
}
```

If the document you're importing has a custom CIUS/extension not supported by this library, you need to register the
preset implementing that specification when instantiating the reader:
```php
use Einvoicing\Readers\UblReader;
use Acme\Invoicing\Presets\CustomPreset;

$reader = new UblReader();
$reader->registerPreset(CustomPreset::class);
$inv = $reader->import($document);
```

Check the [Creating custom presets](creating-custom-presets.md) page for more information.

## Validating invoices
It is good practice to validate an invoice against the business rules defined in the European standard and its preset
(if using one) before exporting to and after importing from a document.

This step is extremely simple as it only requires calling one method from the invoice instance:
```php
use Einvoicing\Exceptions\ValidationException;

try {
    $inv->validate();
} catch (ValidationException $e) {
    // The invoice is not valid (see exception for more details)
}
```

The [`Invoice::validate()`](../reference/invoice.md#validate) method does not return any value and instead will throw an
exception when the invoice is not valid.

!!! warning
    All readers and writers in this library are silent: they will read and export documents without throwing a
    validation exception **even if the invoice is not valid**.

    Unless you explicitly ask the invoice to be validated, you cannot be sure if it complies with the relevant
    specifications.
