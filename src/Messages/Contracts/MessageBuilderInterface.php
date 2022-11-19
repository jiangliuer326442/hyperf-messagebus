<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Messages\Contracts;

use Mustafa\Messagebus;

interface MessageBuilderInterface
{
    /**
     * 构建消息体.
     * @param string $event
     * @param array $data
     * @param string $key
     */
    public function builder(string $event, array $data, string $key = ''): MessageBus\Messages\Structs\MessageStruct;

}
