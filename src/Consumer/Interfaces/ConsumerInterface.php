<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Interfaces;

use Mustafa\Messagebus;

interface ConsumerInterface
{
    /**
     * 消费消息.
     * @param string $consumer 消费者名称
     * @param string[] $events 消费者关注的事件
     * @return MessageBus\Messages\Structs\MessageStruct[] 消息体
     */
    public function consume(string $consumer, array $events): array;

    /**
     * 消息确认
     * @return bool
     */
    public function commit(): bool;
}
