<?php
namespace Einvoicing\Attachment;

class ExternalAttachment extends Attachment {
    protected $uri;

    /**
     * Get external URI
     * @return string Attachment external URI
     */
    public function getUri(): string {
        return $this->uri;
    }


    /**
     * Attachment external URI
     * @param  string $uri Attachment URI
     * @return self        Attachment instance
     */
    public function setUri(string $uri): self {
        $this->uri = $uri;
        return $this;
    }
}
