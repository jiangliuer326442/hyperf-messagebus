<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Producer\Kafka;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\PoolInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use longlang\phpkafka\Producer\ProduceMessage;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;
use Psr\Container\ContainerInterface;

class KafkaTopicsConnection extends BaseConnection implements ConnectionInterface, MessageBus\Producer\Interfaces\ProducerInterface
{

    private Producer $producer;

    private MessageBus\Messages\Contracts\MessageBuilderInterface $messageBuilder;

    public function __construct(ContainerInterface $container, PoolInterface $pool, protected ConfigInterface $config)
    {
        parent::__construct($container, $pool);

        $this->reconnect();
    }

    public function produce(string $topic, MessageBus\Messages\Structs\MessageStruct ...$message_structes): bool
    {
        $produceMessagees = [];
        foreach ($message_structes as $messageStruct) {
            $message = serialize($messageStruct->toArray());
            $produceMessagees[] = new ProduceMessage($topic, $message, $messageStruct->headers->getMessageId(), $messageStruct->headers->toArray());
        }
        $this->producer->sendBatch($produceMessagees);
        return true;
    }

    public function getActiveConnection(): static
    {
        if ($this->check()) {
            return $this;
        }

        if (!$this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $this;
    }

    public function reconnect(): bool
    {
        // 生产者
        $this->producer = new Producer($this->_getKafkaConf());

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    private function _getKafkaConf(): ProducerConfig
    {
        $config = new ProducerConfig();
        $config->setBrokers($this->config->get('messagebus.default.drivers.' . MessageBus\Constants\MessageBusType::KAFKA . '.host'));
        $config->setUpdateBrokers(false);
        return $config;
    }
}
