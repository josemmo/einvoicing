<?php
namespace Tests\Writer;

use Einvoicing\AllowanceCharge\Allowance;
use Einvoicing\AllowanceCharge\Charge;
use Einvoicing\Invoice\PeppolInvoice;
use Einvoicing\InvoiceLine\InvoiceLine;
use Einvoicing\Party\Party;
use Einvoicing\Writer\UblWriter;
use PHPUnit\Framework\TestCase;

final class UblWriterTest extends TestCase {
    /** @var UblWriter */
    private $writer;

    protected function setUp(): void {
        $this->writer = new UblWriter();
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

        // Validate response
        return (strpos($res, '<ns3:result>SUCCESS</ns3:result>') !== false);
    }

    public function testCanGenerateValidInvoice(): void {
        $seller = new Party();
        $seller->setCompanyId('COMPANY_ID')
            ->setName('Seller Name Ltd.')
            ->setTradingName('Seller Name')
            ->setVatNumber('ESA00000000')
            ->setAddress(['Fake Street 123'])
            ->setCity('Springfield')
            ->setCountry('ES');
        
        $buyer = new Party();
        $buyer->setName('Buyer Name Ltd.')
            ->setCountry('ES');
        
        $payee = new Party();
        $payee->setCompanyId('PAYEE_ID')
            ->setName('Payee Name Ltd.');
        
        $complexLine = new InvoiceLine();
        $complexLine->setName('Line #1')
            ->setDescription('The description for the first line')
            ->setPrice(10.5, 5)
            ->setQuantity(27)
            ->setVatRate(21)
            ->addCharge((new Charge)->setReason('Handling and shipping')->setAmount(10.1234));

        $inv = new PeppolInvoice();
        $inv->setNumber('ABC123')
            ->setIssueDate(new \DateTime('-3 days'))
            ->setDueDate(new \DateTime('+30 days'))
            ->setSeller($seller)
            ->setBuyer($buyer)
            ->setPayee($payee)
            ->addLine($complexLine)
            ->addLine((new InvoiceLine)->setName('Line #2')->setPrice(40, 2)->setVatRate(21)->setQuantity(4))
            ->addLine((new InvoiceLine)->setName('Line #3')->setPrice(0.56)->setVatRate(10)->setQuantity(2))
            ->addAllowance((new Allowance)->setReason('5% discount')->setAmount(5)->markAsPercentage()->setVatRate(21));

        // Validate invoice
        $contents = $this->writer->export($inv);
        $this->assertTrue($this->validateInvoice($contents, 'ubl'));
    }
}
