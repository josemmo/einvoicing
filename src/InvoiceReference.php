<?php
namespace Einvoicing;

use DateTime;

class InvoiceReference {
    protected $value;
    protected $issueDate;

    /**
     * Class constructor
     * @param string        $value     Value
     * @param DateTime|null $issueDate Issue date
     */
    public function __construct(string $value, ?DateTime $issueDate=null) {
        $this->setValue($value);
        $this->setIssueDate($issueDate);
    }


    /**
     * Get value
     * @return string Value
     */
    public function getValue(): string {
        return $this->value;
    }


    /**
     * Set value
     * @param  string $value Value
     * @return self          Invoice reference instance
     */
    public function setValue(string $value): self {
        $this->value = $value;
        return $this;
    }


    /**
     * Get issue date
     * @return DateTime|null Issue date
     */
    public function getIssueDate(): ?DateTime {
        return $this->issueDate;
    }


    /**
     * Set issue date
     * @param  DateTime|null $issueDate Issue date
     * @return self                     Invoice reference instance
     */
    public function setIssueDate(?DateTime $issueDate): self {
        $this->issueDate = $issueDate;
        return $this;
    }
}
