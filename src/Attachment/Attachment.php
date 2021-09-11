<?php
namespace Einvoicing\Attachment;

abstract class Attachment {
    protected $id = null;
    protected $documentTypeCode = null;
    protected $description = null;

    /**
     * Get attachment ID
     * @return string|null Attachment ID
     */
    public function getId(): ?string {
        return $this->id;
    }


    /**
     * Set attachment ID
     * @param  string $id Attachment ID
     * @return self       Attachment instance
     */
    public function setId(string $id): self {
        $this->id = $id;
        return $this;
    }


    /**
     * Get document type code
     * @return string|null Attachment document type code
     */
    public function getDocumentTypeCode(): ?string {
        return $this->documentTypeCode;
    }


    /**
     * Set document type code (should always be 130)
     * @param  string|null $documentTypeCode Attachment document type code
     * @return self                          Attachment instance
     */
    public function setDocumentTypeCode(?string $documentTypeCode): self {
        $this->documentTypeCode = $documentTypeCode;
        return $this;
    }


    /**
     * Get description
     * @return string|null Attachment description
     */
    public function getDescription(): ?string {
        return $this->description;
    }


    /**
     * Set description
     * @param  string|null $description Attachment description
     * @return self                     Attachment instance
     */
    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }
}
