<?php
namespace Einvoicing\Attachment;

class EmbeddedAttachment extends Attachment {
    protected $filename = null;
    protected $mimeCode = null;
    protected $content;

    /**
     * Get filename
     * @return string|null Filename
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
     * Get content
     * @return string Attachment content
     */
    public function getContent(): string {
        return $this->content;
    }


    /**
     * Set content
     * @param string $content Attachment content
     * @return self           Attachment instance
     */
    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }
}
