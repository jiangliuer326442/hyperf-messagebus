<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Messages\Structs;

class MessageStructHeaderStruct
{
    private string $event;

    private string $messageId;

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent(string $event): void
    {
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     */
    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEvent(),
            'message_id' => $this->getMessageId(),
        ];
    }

    public function loadArray($array): self
    {
        $this->event = $array['event'];
        $this->messageId = $array['message_id'];
        return $this;
    }
}
