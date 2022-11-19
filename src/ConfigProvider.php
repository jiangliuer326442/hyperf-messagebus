<?php

declare(strict_types=1);

namespace Mustafa\Messagebus;

use Mustafa\Messagebus;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                MessageBus\Messages\Contracts\MessageBuilderInterface::class => MessageBus\Messages\MessageBuilderImpl::class,
                MessageBus\Producer\Interfaces\ProducerInterface::class => MessageBus\Producer\Implements\ProducerImplement::class,
            ],
            'listeners' => [
                Messagebus\Consumer\Listeners\RoundProcessHandle::class,
                MessageBus\Consumer\Listeners\MetricProcessHandle::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for async queue.',
                    'source' => __DIR__ . '/../publish/messagebus.php',
                    'destination' => BASE_PATH . '/config/autoload/messagebus.php',
                ],
            ],
        ];
    }
}