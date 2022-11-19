<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Events;

use Mustafa\Messagebus;

class AfterProcessHandle
{
    public function __construct(
        public MessageBus\Consumer\AbstractProcess $process,
        public string $consumerName,
        public MessageBus\Messages\Structs\MessageStruct $messageStruct
    )
    {
    }
}
