# Contributing
Thanks for taking your time to contribute to this project!
This document will get you on the right track to help improve eInvoicing.

## How to Get Started
Use the following sites to get more information about the European electronic invoicing specification:

- [EU e-Invoicing core concepts](https://josemmo.github.io/einvoicing/getting-started/eu-einvoicing-concepts/)
- [Compliance with the European standard on eInvoicing](https://ec.europa.eu/cefdigital/wiki/x/ggTvB)
- [Obtaining a copy of the European standard on eInvoicing](https://ec.europa.eu/cefdigital/wiki/x/kgLvB)
- [UBL Invoice fields](https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/)
- [CEF eInvoicing Validator](https://www.itb.ec.europa.eu/invoice/upload)

## PR Requirements
Before opening a Pull Request, please make sure your code meets the following requirements:

### 1. Uses `develop` as the base branch
The main repository branch is only for stable releases.

### 2. Passes static analysis inspection
```
vendor/bin/phan --allow-polyfill-parser
```

### 3. Passes all tests
```
vendor/bin/simple-phpunit --testdox
```

### 4. Complies with EN 16931
Although the most popular European Invoicing CIUS is [PEPPOL BIS Billing 3.0](https://docs.peppol.eu/poacc/billing/3.0/),
the real deal is the "European Standard for Electronic invoicing" or EN 16931.

This means that, while most users will use the [Peppol](src/Presets/Peppol.php) preset for reading and writing invoices,
there are other CIUS/extensions from various member states and business sectors which might not be an exact match to PEPPOL.

Because the one thing all these specifications have in common is EN 16931, fields and methods you add to the library
must have the same names as they do in the European Standard.
