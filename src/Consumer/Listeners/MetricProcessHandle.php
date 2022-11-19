<?php

namespace Mustafa\Messagebus\Consumer\Listeners;

use Mustafa\Messagebus;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Hyperf\Process\Event\PipeMessage;

class MetricProcessHandle implements ListenerInterface
{
    public function listen(): array
    {
        return [
            PipeMessage::class,
        ];
    }

    public function process(object $event): void
    {
        $factory = make(MetricFactoryInterface::class);
        $inner = $event->data;
        if ($inner instanceof MessageBus\Messages\Structs\ConsumerCostData){
            $messageJobCostGauge = $factory->makeGauge("messagebus_job_cost", [
                "modelName",
                "consumerName",
                "event",
            ]);
            $messageJobCostGauge->with(
                $inner->modelName,
                $inner->consumerName,
                $inner->eventName
            )->set((
                    $inner->etime
                    -
                    $inner->btime
                )*1000);
            $messageJobDelayGauge = $factory->makeGauge("messagebus_job_delay", [
                "modelName",
                "consumerName",
                "event",
            ]);
            $messageJobDelayGauge->with(
                $inner->modelName,
                $inner->consumerName,
                $inner->eventName
            )->set((
                    $inner->btime
                    -
                    $inner->happenTime
                )*1000);
        }
    }
}
