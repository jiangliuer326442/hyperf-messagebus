<?php

namespace Mustafa\Messagebus\Messages\Structs;

class ConsumerCostData
{
    public string $modelName;

    public string $consumerName;

    public string $eventName;

    public float $btime;

    public float $etime;

    public float $happenTime;

    /**
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param string $modelName
     */
    public function setModelName(string $modelName): void
    {
        $this->modelName = $modelName;
    }

    /**
     * @return string
     */
    public function getConsumerName(): string
    {
        return $this->consumerName;
    }

    /**
     * @param string $consumerName
     */
    public function setConsumerName(string $consumerName): void
    {
        $this->consumerName = $consumerName;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     */
    public function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    /**
     * @return float
     */
    public function getBtime(): float
    {
        return $this->btime;
    }

    /**
     * @param float $btime
     */
    public function setBtime(float $btime): void
    {
        $this->btime = $btime;
    }

    /**
     * @return float
     */
    public function getEtime(): float
    {
        return $this->etime;
    }

    /**
     * @param float $etime
     */
    public function setEtime(float $etime): void
    {
        $this->etime = $etime;
    }

    /**
     * @return float
     */
    public function getHappenTime(): float
    {
        return $this->happenTime;
    }

    /**
     * @param float $happenTime
     */
    public function setHappenTime(float $happenTime): void
    {
        $this->happenTime = $happenTime;
    }


}
