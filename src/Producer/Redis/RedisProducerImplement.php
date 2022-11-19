<?php

namespace Mustafa\Messagebus\Producer\Redis;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisProducerImplement implements MessageBus\Producer\Interfaces\ProducerInterface
{

    public function __construct(protected ContainerInterface $container, protected ConfigInterface $config){
    }

    public function produce(string $topic, MessageBus\Messages\Structs\MessageStruct ...$message_structes): bool
    {
        $redis = $this->container->get(RedisFactory::class)->get($this->config->get('messagebus.default.drivers.' . MessageBus\Constants\MessageBusType::REDIS . '.pool'));
        $pipe = $redis->pipeline();
        foreach ($message_structes as $messageStruct) {
            $pipe->xAdd($topic, "*", array_merge($messageStruct->headers->toArray(), $messageStruct->payload->toArray()));
        }

        $pipe->exec();

        return true;
    }
}
