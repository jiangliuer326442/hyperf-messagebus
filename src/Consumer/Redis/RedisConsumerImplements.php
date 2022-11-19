<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Redis;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisConsumerImplements implements MessageBus\Consumer\Interfaces\ConsumerInterface
{
    private array $acknowledge_items = [];

    private string $consumer_name;

    #[Inject]
    private MessageBus\Messages\Contracts\MessageBuilderInterface $messageBuilder;

    #[Inject]
    private ContainerInterface $container;

    #[Inject]
    private ConfigInterface $config;

    public function __construct(
        protected array $consumers,
        protected string $model_name
    ) {
    }

    public function consume(string $consumer, array $events): array
    {
        $this->consumer_name = $this->model_name . '-' . $consumer;
        $topics = $this->consumers[$consumer]['topics'];
        $redis = $this->container->get(RedisFactory::class)->get(
            $this->config->get('messagebus.default.drivers.'.MessageBus\Constants\MessageBusType::REDIS.'.pool')
        );
        $pipe = $redis->pipeline();
        foreach ($topics as $topic) {
            $pipe->xReadGroup(
                $this->consumer_name,
                $this->model_name . '-' . $consumer . '-' . php_uname('n'),
                [$topic => '>'],
                $this->config->get('messagebus.' . $this->model_name . '.drivers.' . MessageBus\Constants\MessageBusType::REDIS . '.consume_num'),
                $this->config->get('messagebus.' . $this->model_name . '.consumer_interval')
            );
        }
        $ret = $pipe->exec();
        $list = [];
        $acknowledge_items = [];
        $messageStructArr = [];
        foreach ($ret as $ret_topic) {
            if ($ret_topic) {
                $_topic = array_keys($ret_topic)[0];
                if (!isset($acknowledge_items[$_topic])){
                    $acknowledge_items[$_topic] = [];
                }
                foreach ($ret_topic[$_topic] as $_message_key => $_message_item){
                    $acknowledge_items[$_topic][] = $_message_key;
                    if (isset($_message_item['event']) && in_array($_message_item['event'], $events)){
                        $_event = $_message_item['event'];
                        unset($_message_item['event']);
                        unset($_message_item['message_id']);
                        $messageStruct = $this->messageBuilder->builder($_event, $_message_item, $_message_key);
                        $messageStructArr[] = $messageStruct;
                        return $messageStructArr;
                    }
                }
            }
        }
        if ($acknowledge_items){
            $this->acknowledge_items = $acknowledge_items;
        }else{
            $this->acknowledge_items = [];
        }
        return $list;
    }

    public function commit(): bool
    {
        $redis = $this->container->get(RedisFactory::class)->get(
            $this->config->get('messagebus.default.drivers.'.MessageBus\Constants\MessageBusType::REDIS.'.pool')
        );
        $pipe = $redis->pipeline();
        if ($this->acknowledge_items){
            foreach ($this->acknowledge_items as $_topic => $_message_ids){
                $pipe->xAck($_topic, $this->consumer_name, $_message_ids);
            }
        }
        $pipe->exec();
        return true;
    }
}
