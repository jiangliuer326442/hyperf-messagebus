<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Producer\Implements;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;

class ProducerImplement implements MessageBus\Producer\Interfaces\ProducerInterface
{
    private MessageBus\Producer\Interfaces\ProducerInterface $connection;

    public function __construct(protected ConfigInterface $config)
    {
        $default_driver = $this->config->get('messagebus.default.default_driver');
        if ($default_driver === MessageBus\Constants\MessageBusType::KAFKA) {
            $kafkaTopicsPool = make(MessageBus\Producer\Kafka\KafkaTopicsPool::class);
            $this->connection = $kafkaTopicsPool->get();
        }else{
            $this->connection = make(MessageBus\Producer\Redis\RedisProducerImplement::class);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function produce(string $topic, MessageBus\Messages\Structs\MessageStruct ...$message_struct): bool
    {
        // topic产生消息
        $this->connection->produce(
            $topic,
            ...$message_struct
        );
        return true;
    }
}
