# Creating custom presets
Presets are the way in which this library implements CIUS and extensions. You can find built-in presets in the
`\Einvoicing\Presets` namespace.

Defining a custom preset is, in most cases, a simple and quick process.
You should start by creating a new class that extends `\Einvoicing\Presets\AbstractPreset`:
```php
namespace Acme\Invoicing\Presets;

use Einvoicing\Presets\AbstractPreset;

class CustomPreset extends AbstractPreset {
    public function getSpecification(): string {
        return "urn:cen.eu:en16931:2017#compliant#urn:acme.com:MY-PRESET:1.0.0";
    }
}
```

The only method you are required to define is `getSpecification()` as it returns the specification identifier of the
CIUS/extension you're implementing.

With that, you have created a working custom preset. Next time you instantiate an invoice from it, the latter will be
set to the defined specification identifier:
```php
use Acme\Invoicing\Presets\CustomPreset;
use Einvoicing\Invoice;

$inv = new Invoice(CustomPreset::class);
echo $inv->getSpecification(); // urn:cen.eu:en16931:2017#compliant#urn:acme.com:MY-PRESET:1.0.0
```

## Adding and removing business rules
Some presets may require additional business rules for an invoice to be considered valid apart from the ones defined
in the EN 16931, which will be enforced when calling [`Invoice::validate()`](../reference/invoice.md#validate).

Given that rules are internally stored as PHP functions or callables, they can essentially be as complex as you need
them to be.

To add new rules to your preset, create a new method called `getRules()` in your preset class:
```php
use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;
use function mb_strpos;

class CustomPreset extends AbstractPreset {
    // [...]

    public function getRules(): array {
        $res = [];

        $res['BR-ACME-01'] = static function(Invoice $inv) {
            if (!empty($inv->getAllowances())) return "No allowances at the invoice level are allowed";
        };

        $res['BR-ACME-02'] = static function(Invoice $inv) {
            if (mb_strpos($inv->getNumber(), 'ACME-') !== 0) return "Invoice number must start with 'ACME-'";
        };

        return $res;
    }
}
```

As you can see in the above example, a rule returns `void` or **an empty value** when the invoice passes that particular
validation, and a `string` detailing the non-compliance reason otherwise.

!!! note
    The order in which rules are defined matters, as they will be evaluated in the same fashion.
    EN 16931 business rules are **always validated before** custom presets rules.

Removing or modifying rules from the European standard is also possible by overriding them:
```php
$res['BR-03'] = static function(Invoice $inv) {
    // Now this test (rule) will always pass
};
```

## Custom invoice modifications
There may be some occasions when presets will require to initialize the invoice instance with some default values.
For example, setting a rounding matrix to define the number of decimal places allowed in certain fields.

To achieve that, you can make use of the `setupInvoice()` method of a preset class, which will be called just before
finishing creating a new invoice instance:
```php
use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;

class CustomPreset extends AbstractPreset {
    // [...]

    public function setupInvoice(Invoice $invoice) {
        $invoice->setRoundingMatrix([
            "line/netAmount" => 4,
            "" => 2
        ]);
    }
}
```

## Custom document modifications
Presets can also modify the invoice document that will be exported. In order to do so, your preset needs to implement
the `finalizeUbl()` method, which will be called after the UBL XML document tree has been generated:
```php
use Einvoicing\Invoice;
use Einvoicing\Presets\AbstractPreset;
use UXML\UXML;

class CustomPreset extends AbstractPreset {
    // [...]

    public function finalizeUbl(UXML $xml): UXML {
        $xml->add('CustomNode', 'The contents of this node');
        return $xml;
    }
}
```
