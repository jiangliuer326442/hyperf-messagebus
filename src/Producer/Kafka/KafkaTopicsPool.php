<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Producer\Kafka;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool as BasePool;
use Psr\Container\ContainerInterface;

class KafkaTopicsPool extends BasePool
{
    public function __construct(ContainerInterface $container, protected ConfigInterface $config)
    {
        $pool_config = $this->config->get('messagebus.default.drivers.' . MessageBus\Constants\MessageBusType::KAFKA . '.pool');
        parent::__construct($container, $pool_config);
    }

    public function createConnection(): ConnectionInterface
    {
        return new KafkaTopicsConnection($this->container, $this, $this->config);
    }
}
