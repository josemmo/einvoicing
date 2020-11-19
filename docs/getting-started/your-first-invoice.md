# Creating your first invoice
There are two ways of creating an invoice from scratch: either instantiating a new blank
[`Invoice`](../reference/invoice.md) or by using a preset (recommended).

In this library, **presets** are the implementation of CIUS and extensions as defined in the European standard.
Most probably, the CIUS you'll be using when creating invoices will be **PEPPOL BIS** as it is widely used in multiple
countries and business sectors, but you can use any preset defined in the `\Einvoicing\Presets` namespace or even
create a custom one yourself.

To create a minimal valid invoice, you'll need four elements:

- The invoice instance ([`Invoice`](../reference/invoice.md))
- A seller party ([`Party`](../reference/party.md))
- A buyer party ([`Party`](../reference/party.md))
- Some invoice lines ([`InvoiceLine`](../reference/invoice-line.md))

Start by creating the invoice instance:
```php
use DateTime;
use Einvoicing\Invoice;
use Einvoicing\Presets;

$inv = new Invoice(Presets\Peppol::class);
$inv->setNumber('F-202000012')
    ->setIssueDate(new DateTime('2020-11-01'))
    ->setDueDate(new DateTime('2020-11-30'));
```

!!! note
    You can instantiate an invoice without passing a preset argument, although this is highly discouraged.
    Setting a preset enhances the invoice in multiple aspects as it:

    - Sets the correct specification identifier
    - Overrides the number of decimal places of multiple fields
    - Adds/removes custom validation rules defined by the CIUS or extension implemented by the preset

    In short, it makes creating invoices easier.


Then, declare both parties involved in the transaction (seller and buyer):
```php
use Einvoicing\Identifier;
use Einvoicing\Party;

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

$buyer = new Party();
$buyer->setElectronicAddress(new Identifier('ES12345', '0002'))
    ->setName('Buyer Name Ltd.')
    ->setCountry('FR');
$inv->setBuyer($buyer);
```

Lastly, add the invoice lines:
```php
use Einvoicing\InvoiceLine;

// 4 items priced at €40/unit + 16% VAT
$firstLine = new InvoiceLine();
$firstLine->setName('Product Name')
    ->setPrice(40)
    ->setVatRate(16)
    ->setQuantity(4);
$inv->addLine($firstLine);

// 27 items price at €10 per 5 units + 4% VAT
$secondLine = new InvoiceLine();
$secondLine->setName('Line #2')
    ->setDescription('The description for the second line')
    ->setPrice(10, 5)
    ->setQuantity(27)
    ->setVatRate(4);
$inv->addLine($secondLine);
```

Congrats! You have successfully created your first EN 16931 compliant invoice.

Now what's left is to export the invoice to a UBL/CII document that can be easily exchanged with third-parties.
See the next chapter for more information.
