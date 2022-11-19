<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Producer\Interfaces;

use Mustafa\Messagebus;

interface ProducerInterface
{
    /**
     * 生产者 生产消息.
     * @param string $topic
     * @param MessageBus\Messages\Structs\MessageStruct ...$message_struct
     * @return bool
     */
    public function produce(string $topic, MessageBus\Messages\Structs\MessageStruct ...$message_struct): bool;
}
