<?php
namespace Einvoicing\Traits;

use Einvoicing\Attachment;
use OutOfBoundsException;

trait AttachmentsTrait {
    protected $attachments = [];

    /**
     * Get attachments
     * @return Attachment[] Array of attachments
     */
    public function getAttachments(): array {
        return $this->attachments;
    }


    /**
     * Add attachment
     * @param  Attachment $attachment Attachment
     * @return self                   This instance
     */
    public function addAttachment(Attachment $attachment): self {
        $this->attachments[] = $attachment;
        return $this;
    }


    /**
     * Remove attachment
     * @param  int  $index Attachment index
     * @return self        This instance
     * @throws OutOfBoundsException if attachment index is out of bounds
     */
    public function removeAttachment(int $index): self {
        if ($index < 0 || $index >= count($this->attachments)) {
            throw new OutOfBoundsException('Could not find attachment by index');
        }
        array_splice($this->attachments, $index, 1);
        return $this;
    }


    /**
     * Clear all attachments
     * @return self This instance
     */
    public function clearAttachments(): self {
        $this->attachments = [];
        return $this;
    }
}
