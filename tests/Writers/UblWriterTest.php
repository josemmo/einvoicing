<?php
namespace Tests\Writers;

use DateTime;
use Einvoicing\AllowanceOrCharge;
use Einvoicing\Attachment;
use Einvoicing\Identifier;
use Einvoicing\Invoice;
use Einvoicing\InvoiceLine;
use Einvoicing\InvoiceReference;
use Einvoicing\Party;
use Einvoicing\Payments\Payment;
use Einvoicing\Presets\Peppol;
use Einvoicing\Writers\UblWriter;
use PHPUnit\Framework\TestCase;
use UXML\UXML;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use function array_map;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function random_int;
use function strpos;
use function time;

final class UblWriterTest extends TestCase {
    /** @var UblWriter */
    private $writer;

    protected function setUp(): void {
        $this->writer = new UblWriter();
    }

    private function getSampleInvoice(): Invoice {
        $seller = (new Party)
            ->setElectronicAddress(new Identifier('9482348239847239874', '0088'))
            ->setCompanyId(new Identifier('COMPANY_ID', '0183'))
            ->setTaxRegistrationId(new Identifier('12345678')) // NOTE: Missing scheme on purpose
            ->setName('Seller Name Ltd.')
            ->setTradingName('Seller Name')
            ->setVatNumber('ESA00000000')
            ->setAddress(['Fake Street 123'])
            ->setCity('Springfield')
            ->setCountry('ES');

        $buyer = (new Party)
            ->setElectronicAddress(new Identifier('ES12345', '0002'))
            ->setName('Buyer Name Ltd.')
            ->setCountry('ES');
        
        $complexLine = (new InvoiceLine)
            ->setName('Line #1')
            ->setDescription('The description for the first line')
            ->setPrice(10.5, 5)
            ->setQuantity(27)
            ->setVatRate(21)
            ->addCharge((new AllowanceOrCharge)->setReason('Handling and shipping')->setAmount(10.1234));

        $externalAttachment = (new Attachment)
            ->setId(new Identifier('ATT-4321'))
            ->setDescription('A link to an external attachment')
            ->setExternalUrl('https://www.example.com/document.pdf');
        $embeddedAttachment = (new Attachment)
            ->setId(new Identifier('ATT-1234'))
            ->setFilename('ATT-1234.pdf')
            ->setMimeCode('application/pdf')
            ->setContents('The attachment raw contents');

        $invoice = new Invoice(Peppol::class);
        $invoice->setNumber('ABC-123')
            ->setIssueDate(new DateTime('-3 days'))
            ->setDueDate(new DateTime('+30 days'))
            ->setBuyerReference('REF-0172637')
            ->addPrecedingInvoiceReference(new InvoiceReference('INV-123'))
            ->setTenderOrLotReference('PPID-123')
            ->setContractReference('123Contractref')
            ->setSeller($seller)
            ->setBuyer($buyer)
            ->addLine($complexLine)
            ->addLine((new InvoiceLine)->setName('Line #2')->setPrice(40, 2)->setVatRate(21)->setQuantity(4))
            ->addLine((new InvoiceLine)->setName('Line #3')->setPrice(0.56)->setVatRate(10)->setQuantity(2))
            ->addLine((new InvoiceLine)->setName('Line #4')->setPrice(0.56)->setVatRate(10)->setQuantity(2))
            ->addAllowance(
                (new AllowanceOrCharge)->setReason('5% discount')
                    ->setFactorMultiplier(5)
                    ->setBaseAmount(149.0634)
                    ->markAsPercentage()
                    ->setAmount(7.45317)
                    ->setVatRate(21)
            )
            ->addAttachment((new Attachment)->setId(new Identifier('INV-123', 'ABT')))
            ->addAttachment($externalAttachment)
            ->addAttachment($embeddedAttachment);
        
        return $invoice;
    }

    private function validateInvoice(string $contents, string $type): bool {
        // Build SOAP request
        $req  = '<?xml version="1.0" encoding="UTF-8"?>';
        $req .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"';
        $req .= ' xmlns:v1="http://www.gitb.com/vs/v1/" xmlns:v11="http://www.gitb.com/core/v1/">';
        $req .= '<soapenv:Body>';
        $req .= '<v1:ValidateRequest>';
        $req .= '<sessionId>' . time() . '-' . random_int(0, 9999) . '</sessionId>';
        $req .= '<input name="type" embeddingMethod="STRING"><v11:value>' . $type . '</v11:value></input>';
        $req .= '<input name="xml" embeddingMethod="STRING"><v11:value>';
        $req .= '<![CDATA[' . $contents . ']]>';
        $req .= '</v11:value></input>';
        $req .= '</v1:ValidateRequest>';
        $req .= '</soapenv:Body>';
        $req .= '<soapenv:Envelope>';

        // Send cURL request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://www.itb.ec.europa.eu/invoice/api/validation',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $req,
            CURLOPT_HTTPHEADER => ['Content-Type: application/xml']
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        // Validate response
        return (strpos($res, '<ns3:result>SUCCESS</ns3:result>') !== false);
    }

    public function testCanGenerateValidInvoice(): void {
        $invoice = $this->getSampleInvoice();
        $invoice->validate();
        $contents = $this->writer->export($invoice);
        $this->assertTrue($this->validateInvoice($contents, 'ubl'));
    }

    public function testCanGenerateValidCreditNote(): void {
        $invoice = $this->getSampleInvoice();
        $invoice->setType(Invoice::TYPE_CREDIT_NOTE);
        $invoice->addPayment((new Payment)->setMeansCode('10')->setMeansText('In cash'));
        $invoice->validate();
        $contents = $this->writer->export($invoice);
        $this->assertTrue($this->validateInvoice($contents, 'credit'));
    }

    public function testCanHaveLinesWithForcedDuplicateIdentifiers(): void {
        $invoice = $this->getSampleInvoice();
        $invoice->getLines()[1]->setId('DuplicateId');
        $invoice->getLines()[2]->setId('DuplicateId');
        $invoice->getLines()[3]->setId('DuplicateId');
        $xml = UXML::fromString($this->writer->export($invoice));
        $actualLineIds = array_map(function(UXML $item) {
            return $item->asText();
        }, $xml->getAll('cac:InvoiceLine/cbc:ID'));
        $this->assertEquals(['1', 'DuplicateId', 'DuplicateId', 'DuplicateId'], $actualLineIds);
    }

    public function testCanAutogenerateInvoiceLineIdentifiers(): void {
        $invoice = $this->getSampleInvoice();
        $invoice->getLines()[1]->setId('1');
        $invoice->getLines()[2]->setId('AnotherCustomId');
        $xml = UXML::fromString($this->writer->export($invoice));
        $actualLineIds = array_map(function(UXML $item) {
            return $item->asText();
        }, $xml->getAll('cac:InvoiceLine/cbc:ID'));
        $this->assertEquals(['2', '1', 'AnotherCustomId', '3'], $actualLineIds);
    }
}
