<?php
namespace Einvoicing;

class OrderReference {

    /** @var string|null */
	protected $id;
    /** @var string|null */
	protected $salesOrderId;

	public function __construct(?string $id = null, ?string $salesOrderId = null) {
		$this->id = $id;
		$this->salesOrderId = $salesOrderId;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function setId(?string $id): OrderReference {
		$this->id = $id;

		return $this;
	}

	public function getSalesOrderId(): ?string {
		return $this->salesOrderId;
	}

	public function setSalesOrderId(?string $salesOrderId): OrderReference {
		$this->salesOrderId = $salesOrderId;

		return $this;
	}
}
