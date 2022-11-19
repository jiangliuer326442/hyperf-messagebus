# swoole hyperf框架 消息总线
支持`kafka`和`redis stream`队列，生产者往消息总线中放入事件和相关数据

按照功能模块创建消费者进程，每个模块可以有多个消费者订阅多个事件，返回具体的消费者，订阅获得的事件以及与该事件相关的参数，供业务方逻辑处理

为生产者和消费者建立了连接池，支持消息的批量发送和批量接收

基于 `hyperf/metric` 实现对消息总线的监控，主要监控消息的延迟程度以及消息被用来处理业务逻辑耗费的时间，指标为 `messagebus_job_cost` 和 `messagebus_job_delay`,可上报到prometheus等用于监控和告警

for example：

直播业务包含主播获得收入事件和直播开播时长事件，他们被放入消息总线中
任务考核模块有每天直播大于60分钟且累计收入大于100元 —— 这个任务本身可以看作一个消费者，消费上述两个事件进行业务逻辑的处理
该sdk会返回任务标识，直播时长事件或者收入事件的相关数据供业务方处理

## 使用向导

### 安装

`composer require mustafa3264/messagebus`

`php bin/hyperf.php vendor:publish mustafa3264/messagebus`

### 配置
```php
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
```

default 部分属于公共配置，规定了消息总线是使用kafka还是redis stream

如果是kafka，如果是kafka，pool定义了 **`生产者`** 的连接池配置，host是kafka的broker地址；如果是redis，sdk使用hyperf自己实现的redis连接池，pool规定具体使用哪个redis连接池

test 部分是具体的业务模块，kafka部分定义了该消费者部分使用的连接池，`consume_num` 是从消息总线单次取出来的消息数，`consumer_interval` 是获取消息的频率

### 创建模块消费者进程

```php
<?php

namespace App\Process;

use Hyperf\Redis\RedisFactory;
use Mustafa\Messagebus\Constants\MessageBusType;
use Mustafa\Messagebus\Consumer\AbstractProcess;

class MessagebusConsumerProcess extends AbstractProcess
{
    public string $modelName = 'test'; // test 模块的消息队列

    public string $name = 'test-eventbus-queme'; // 进程名称

    public int $nums = 1; // 进程数量

    protected int $restartInterval = 120; // 进程重启间隔

    protected function getConsumersWithTopicsAndEvents(): array
    {
        return [ // 模块下的消费者列表 对应消费者订阅的主题（队列）
            'ruby-live25' => [ // 消费者消费的topic
                'topics' => [
                    \App\Constants\KafkaTopics::TEST_TOPIC1,
                ],
                'events' => [ // 消费者关注的事件
                    \App\Constants\MessageBusEvents::EVENTS1,
                ],
            ],
        ];
    }

    protected function handleMessage(string $consumer, string $event, array $payload): void
    {
        print_r($consumer);
        echo PHP_EOL;
        print_r($event);
        echo PHP_EOL;
        print_r($payload);
        echo PHP_EOL;
        echo PHP_EOL;
    }

    protected function beforeMessageBus()
    {
        if ($this->defaultDriver === MessageBusType::REDIS) {
            // 初始化消费组
            $redis = $this->container->get(RedisFactory::class)->get(
                $this->config->get('messagebus.default.drivers.' . $this->defaultDriver . '.pool')
            );
            foreach ($this->getConsumersWithTopicsAndEvents() as $group => $consumer_row) {
                $topics = $consumer_row['topics'];
                foreach ($topics as $_topic) {
                    $redis->rawCommand('xgroup', 'create', $_topic, $this->modelName . '-' . $group, 0);
                }
            }
        }
    }

}
```
`modelName` 是消费者进程所属模块，与上面config的模块配置要求保持一致

`name` 消费者进程名称

`nums` 消费进程的数量

`getConsumersWithTopicsAndEvents` 函数，返回的是消费者订阅的队列和事件列表，这个逻辑根据具体业务实现，可以从配置中心获取，也可以从数据库查询生成

`beforeMessageBus` 该函数在消费者循环启动之前执行，用于初始化，在这里，如果使用redis队列，可以初始化消费组

`handleMessage` 可以拿到消费者，与之关联的事件以及该事件的参数数据，根据业务逻辑进行处理，如校验数据是否符合任务要求，更新任务完成进程，判断任务是否完成等逻辑

### 配置消费者进程

在process.php文件中添加该消费者进程

```php
<?php

declare(strict_types=1);

return [
    App\Process\MessagebusConsumerProcess::class,
];

```

## 联系作者

#### 我的邮箱 
- fanghailiang2016@gmail.com
- hailiang_fang@163.com

#### 微信

**fanghailiang2023**