# Sending documents
This library creates, parses and validates invoicing documents. However, it does not delivery them.

While the EN 16931 defines semantic data models and syntax formats (UBL and CII), delivery is transport-agnostic.
Depending on your target region and compliance mandates, invoices can be delivered through several channels:

- PEPPOL network: An international network for exchanging invoices and other electronic documents between businesses.
- National and tax portals: Official government platforms for direct submission of invoices. For instance,
  [Chorus Pro](https://portail.chorus-pro.gouv.fr/) in France, [FACe](https://face.gob.es/) and
  [FACeB2B](https://faceb2b.gob.es/) in Spain, [SdI](https://www.fatturapa.gov.it/it/sistemainterscambio/cose-il-sdi/)
  in Italy, or [KSeF](https://ksef.mf.gov.pl/) in Poland.
- Hybrid formats: Custom file formats, typically based on PDF/A-3 files, that embed an electronic invoice document to
  then be delivered by email. For example, [ZUGFeRD](https://www.ferd-net.de/standards/zugferd) (or Factur-X) in
  Germany.

## Sending documents over PEPPOL
Sending documents over the PEPPOL network requires access to an Access Point (AP).

While you can host your own AP using open-source software like [Oxalis](https://www.oxalis.network/) or
[phase4](https://github.com/phax/phase4), connecting it to the official network requires joining OpenPeppol as a Service
Provider, pass an accreditation, and pay annual fees.

As an alternative, OpenPeppol maintains a
[list of certified service providers](https://peppol.org/members/peppol-certified-service-providers/) offering managed
commercial AP infrastructure.
It is worth noting that non-profit initiatives like [Let's Peppol](https://letspeppol.org/) runs a free access point for
small to medium-size organizations.

### PHP packages for sending invoices
There are some PHP libraries for sending your exported UBL documents through PEPPOL to ease this process.
For example:

- [getpeppr/getpeppr-php](https://github.com/zerolooplabs/getpeppr-php)
- [tigron/peppyrus-api-php](https://github.com/tigron/peppyrus-api-php)
