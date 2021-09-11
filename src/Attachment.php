<?php
namespace Einvoicing;

class Attachment {
    protected $id = null;
    protected $description = null;
    protected $externalUrl = null;
    protected $filename = null;
    protected $mimeCode = null;
    protected $contents = null;

    /**
     * Get attachment ID
     * @return Identifier|null Attachment ID
     */
    public function getId(): ?Identifier {
        return $this->id;
    }


    /**
     * Set attachment ID
     * @param  Identifier|null $id Attachment ID
     * @return self                Attachment instance
     */
    public function setId(?Identifier $id): self {
        $this->id = $id;
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


    /**
     * Has external URL
     * @return boolean Whether this attachment has an external URL or not
     */
    public function hasExternalUrl(): bool {
        return ($this->externalUrl !== null);
    }


    /**
     * Get external URL
     * @return string|null Attachment external URL
     */
    public function getExternalUrl(): ?string {
        return $this->externalUrl;
    }


    /**
     * Set external URL
     * @param  string|null $externalUrl Attachment external URL
     * @return self                     Attachment instance
     */
    public function setExternalUrl(?string $externalUrl): self {
        $this->externalUrl = $externalUrl;
        return $this;
    }


    /**
     * Get filename
     * @return string|null Attachment filename
     */
    public function getFilename(): ?string {
        return $this->filename;
    }


    /**
     * Set filename
     * @param  string|null $filename Attachment filename
     * @return self                  Attachment instance
     */
    public function setFilename(?string $filename): self {
        $this->filename = $filename;
        return $this;
    }


    /**
     * Get mime code
     * @return string|null Attachment mime code
     */
    public function getMimeCode(): ?string {
        return $this->mimeCode;
    }


    /**
     * Set mime code
     * @param  string|null $mimeCode Attachment mime code
     * @return self                  Attachment instance
     */
    public function setMimeCode(?string $mimeCode): self {
        $this->mimeCode = $mimeCode;
        return $this;
    }


    /**
     * Has embedded contents
     * @return boolean Whether this attachment has embedded contents or not
     */
    public function hasContents(): bool {
        return ($this->contents !== null);
    }


    /**
     * Get embedded contents
     * @return string|null Attachment contents
     */
    public function getContents(): ?string {
        return $this->contents;
    }


    /**
     * Set embedded contents
     * @param  string|null $content Attachment contents
     * @return self                 Attachment instance
     */
    public function setContents(?string $contents): self {
        $this->contents = $contents;
        return $this;
    }
}
