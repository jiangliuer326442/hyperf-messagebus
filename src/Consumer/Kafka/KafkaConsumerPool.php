<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Kafka;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;

class KafkaConsumerPool extends Pool
{

    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected ConfigInterface $config;

    public function __construct(
        protected string $model_name,
        protected array $consumers,
        protected array $currentDriverConfig
    )
    {
        parent::__construct($this->container, $this->currentDriverConfig['pool']);
    }

    public function createConnection(): ConnectionInterface
    {
        return new KafkaConsumerConnection($this, $this->model_name, $this->consumers);
    }
}
