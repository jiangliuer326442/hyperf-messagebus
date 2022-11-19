<?php

declare(strict_types=1);

use Mustafa\Messagebus;

return [
    'test' => [// 业务板块
        'drivers' => [ // 消费者驱动配置
            MessageBus\Constants\MessageBusType::KAFKA => [// kafka
                'pool' => [ // 连接池配置
                    'min_connections' => 1,
                    'max_connections' => 1,
                    'connect_timeout' => 1,
                    'wait_timeout' => 1,
                    'heartbeat' => -1,
                    'max_idle_time' => 60,
                ],
                'consume_num' => 1, // 单次消费消息的数量
            ],
            MessageBus\Constants\MessageBusType::REDIS => [// redis stream
                'consume_num' => 10, // 单次消费消息的数量
            ],
        ],
        'consumer_interval' => 2, // 每多长时间消费一次
    ],
    'default' => [ // 全局配置
        'default_driver' => MessageBus\Constants\MessageBusType::REDIS, // 默认驱动
        'drivers' => [ // 生产者驱动配置
            MessageBus\Constants\MessageBusType::KAFKA => [// kafka
                'pool' => [ // 连接池配置
                    'min_connections' => 1,
                    'max_connections' => 1,
                    'connect_timeout' => 1,
                    'wait_timeout' => 1,
                    'heartbeat' => -1,
                    'max_idle_time' => 60,
                ],
                'host' => env('KAFKA_HOST', 'localhost:9092'),
            ],
            MessageBus\Constants\MessageBusType::REDIS => [// redis stream
                'pool' => 'default',
            ],
        ],
    ],
];
