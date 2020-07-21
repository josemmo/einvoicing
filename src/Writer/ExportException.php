<?php
namespace Einvoicing\Writer;

class ExportException extends \Exception {
    protected $businessRuleId;

    /**
     * Class constructor
     * @param string $brId     Business rule ID
     * @param string $message  Exception message
     */
    public function __construct(string $brId, string $message) {
        $this->businessRuleId = $brId;
        parent::__construct($message);
    }


    /**
     * Get business rule ID
     * @return string Business rule ID
     */
    public function getBusinessRuleId(): string {
        return $this->businessRuleId;
    }


    /**
     * @inheritdoc
     */
    public function __toString(): string {
        return __CLASS__ . ": [{$this->getBusinessRuleId()}] - {$this->message}\n";
    }
}
