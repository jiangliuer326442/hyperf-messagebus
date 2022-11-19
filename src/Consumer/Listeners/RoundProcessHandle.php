<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer\Listeners;

use Mustafa\Messagebus;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\ProcessCollector;

class RoundProcessHandle implements ListenerInterface
{

    private static array $consume_cost = [];

    public function listen(): array
    {
        return [
            MessageBus\Consumer\Events\BeforeProcessHandle::class,
            MessageBus\Consumer\Events\AfterProcessHandle::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof MessageBus\Consumer\Events\BeforeProcessHandle) {
            self::$consume_cost[$event->process->modelName][$event->consumerName][$event->messageStruct->headers->getEvent()]['btime'] = microtime(true);
        } elseif ($event instanceof MessageBus\Consumer\Events\AfterProcessHandle) {
            self::$consume_cost[$event->process->modelName][$event->consumerName][$event->messageStruct->headers->getEvent()]['etime'] = microtime(true);

            $transprotObject = new MessageBus\Messages\Structs\ConsumerCostData();
            $transprotObject->setBtime(self::$consume_cost[$event->process->modelName][$event->consumerName][$event->messageStruct->headers->getEvent()]['btime']);
            $transprotObject->setEtime(self::$consume_cost[$event->process->modelName][$event->consumerName][$event->messageStruct->headers->getEvent()]['etime']);
            $transprotObject->setConsumerName($event->consumerName);
            $transprotObject->setEventName($event->messageStruct->headers->getEvent());
            $transprotObject->setHappenTime($event->messageStruct->payload->getHappenedAt());
            $transprotObject->setModelName($event->process->modelName);
            unset(self::$consume_cost[$event->process->modelName][$event->consumerName][$event->messageStruct->headers->getEvent()]);

            $metric_processes = ProcessCollector::get('metric');
            foreach ($metric_processes as $metricProcess) {
                $socket = $metricProcess->exportSocket();
                $socket->send(serialize($transprotObject));
            }
        }
    }
}
