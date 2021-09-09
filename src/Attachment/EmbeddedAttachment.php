<?php

namespace Einvoicing\Attachment;

class EmbeddedAttachment extends Attachment
{
    protected $filename = null;
    protected $mimeType = null;
    protected $content;

    /**
     * Get filename
     * @return string|null Filename
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Set filename
     * @param string|null $filename Attachment filename
     * @return self                 Attachment instance
     */
    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get mime type
     * @return string|null Attachment Mime Type
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Set mime type
     * @param string|null $mimeType Attachment mime type
     * @return self                 Attachment instance
     */
    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * Get content
     * @return string Attachment content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set content
     * @param string $content Attachment content
     * @return self           Attachment instance
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
