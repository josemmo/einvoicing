# EU e-Invoicing core concepts
Prior to using this library, there are some key concepts about European e-Invoicing you should be aware of.

## EN 16931
The European e-Invoicing standard (EN 16931) describes the **semantic data model** and business rules of an electronic
invoice. In other words, this document (specifically [part 1][1]) specifies **what an invoice is** (fields it must/can
have) and the possible values those fields can contain.

But that's it: it just gives an abstract definition of an invoice without getting into the technical structure (syntax).
We need a format (or *formats*) for representing electronic invoices as documents (files), and the two specifications
allowed by the standard are UBL and CII.

The main purpose of this library is to represent invoices as defined by the European standard and convert them from/to
its document representations in UBL or CII.

## UBL and CII
UBL (Universal Business Language) and CII (UN/CEFACT Cross Industry Invoice) are two XML-based specifications for
exchanging business information. They both already existed prior to the European standard and were then chosen as the
only supported syntaxes for representing invoice documents that comply with EN 16931.

In theory, they can be used interchangeable with respect to EU e-invoices, although some exceptions may apply depending
on additional business rules enforced by a CIUS or an extension.

To determine which field from an European invoice goes to which UBL/CII XML node, there are [mappings][2] defined for
both formats in parts 2 and 3 of the European standard.

## CIUS and extensions
Not all European member states have the same legislation (e.g. tax rates may not be the same across borders) nor all
business sectors require the same set of fields to be present in an invoice (e.g. the energy sector may need a document
to contain the meter info and meter readings of a client while others don't).

To solve this problem, the European e-Invoicing standard proposes the use of CIUS ([Core Invoice Usage Specifications][3])
and Extensions, which are **additional specifications** made by EU state governments or other stakeholders that add or
remove business rules from the EN 16931.

This way, the standard can stay uniform across all European members whilst providing enough flexibility for handling
more particular cases, such us the ones mentioned before.

The most popular CIUS is [PEPPOL BIS Billing 3.0][4], a cross-border specification used by multiple countries and
international companies from both public and private sectors.

[1]: https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Navigating+the+eInvoicing+standard+documentation
[2]: https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Required+syntaxes
[3]: https://ec.europa.eu/digital-building-blocks/sites/display/EINVCOMMUNITY/Registry+of+CIUS+%28Core+Invoice+Usage+Specifications%29+and+Extensions
[4]: http://docs.peppol.eu/poacc/billing/3.0/
