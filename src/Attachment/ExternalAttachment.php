<?php

namespace Einvoicing\Attachment;

class ExternalAttachment extends Attachment
{
    protected $uri;

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri Attachment uri
     * @return self       Attachment instance
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }
}
