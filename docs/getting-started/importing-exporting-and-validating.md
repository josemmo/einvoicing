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
use ACME\Invoicing\Presets\CustomPreset;

$reader = new UblReader();
$reader->registerPreset(CustomPreset::class);
$inv = $reader->import($document);
```

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
