<?php
namespace Einvoicing\Exceptions;

use Exception;

class ExportException extends Exception {
    protected $businessRuleId;

    /**
     * Class constructor
     * @param string      $message Exception message
     * @param string|null $brId    Business rule ID
     */
    public function __construct(string $message, ?string $brId=null) {
        $this->businessRuleId = $brId;
        parent::__construct($message);
    }


    /**
     * Get business rule ID
     * @return string|null Business rule ID
     */
    public function getBusinessRuleId(): ?string {
        return $this->businessRuleId;
    }
}
