<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Messages;

use Mustafa\Messagebus;

class MessageBuilderImpl implements MessageBus\Messages\Contracts\MessageBuilderInterface
{
    public function builder(string $event, array $data, string $key = ''): MessageBus\Messages\Structs\MessageStruct
    {
        $headers_struct = new MessageBus\Messages\Structs\MessageStructHeaderStruct();
        $headers_struct->setEvent($event);
        if (!$key){
            $key = md5(microtime(true) . '-msg');
        }
        $headers_struct->setMessageId($key);

        $payload_struct = new MessageBus\Messages\Structs\MessageStructPayloadStruct();
        $payload_struct->setDatas($data);

        $message_struct = new MessageBus\Messages\Structs\MessageStruct();
        $message_struct->headers = $headers_struct;
        $message_struct->payload = $payload_struct;

        return $message_struct;
    }
}
