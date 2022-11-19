<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Messages\Structs;

class MessageStruct
{
    public MessageStructHeaderStruct $headers;

    public MessageStructPayloadStruct $payload;

    public function loadArray($array): self
    {
        $new_header = new MessageStructHeaderStruct();
        $new_payload = new MessageStructPayloadStruct();
        $this->headers = $new_header->loadArray($array['headers']);
        $this->payload = $new_payload->loadArray($array['payload']);
        return $this;
    }

    public function toArray(): array
    {
        if (isset($this->headers, $this->payload)) {
            return [
                'headers' => $this->headers->toArray(),
                'payload' => $this->payload->toArray(),
            ];
        }
        return [];
    }
}
