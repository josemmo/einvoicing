<?php
namespace Einvoicing\Traits;

use DateTime;

trait PeriodTrait {
    protected $periodStartDate = null;
    protected $periodEndDate = null;

    /**
     * Get period start date
     * @return DateTime|null Period start date
     */
    public function getPeriodStartDate(): ?DateTime {
        return $this->periodStartDate;
    }


    /**
     * Set period start date
     * @param  DateTime|null $periodStartDate Period start date
     * @return self                           This instance
     */
    public function setPeriodStartDate(?DateTime $periodStartDate): self {
        $this->periodStartDate = $periodStartDate;
        return $this;
    }


    /**
     * Get period end date
     * @return DateTime|null Period end date
     */
    public function getPeriodEndDate(): ?DateTime {
        return $this->periodEndDate;
    }


    /**
     * Set period end date
     * @param  DateTime|null $periodEndDate Period end date
     * @return self                         This instance
     */
    public function setPeriodEndDate(?DateTime $periodEndDate): self {
        $this->periodEndDate = $periodEndDate;
        return $this;
    }
}
