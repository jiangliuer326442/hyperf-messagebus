<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Messages\Structs;

class MessageStructPayloadStruct
{
    private string $app;

    private string $host;

    private float $happenedAt;

    private array $datas;

    public function __construct()
    {
        $this->app = env('APP_NAME');
        $this->host = php_uname('n');
        $this->happenedAt = microtime(true);
    }

    /**
     * @return string
     */
    public function getApp(): string
    {
        return $this->app;
    }

    /**
     * @param string $app
     */
    public function setApp(string $app): void
    {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHappenedAt(): float
    {
        return $this->happenedAt;
    }

    /**
     * @param float $happenedAt
     */
    public function setHappenedAt(float $happenedAt): void
    {
        $this->happenedAt = $happenedAt;
    }

    /**
     * @return array
     */
    public function getDatas(): array
    {
        return $this->datas;
    }

    /**
     * @param array $datas
     */
    public function setDatas(array $datas): void
    {
        $this->datas = $datas;
    }

    public function loadArray($array): self
    {
        $this->app = $array['_app'];
        $this->host = $array['_host'];
        $this->happenedAt = $array['_happened_at'];
        unset($array['_app'], $array['_host'], $array['_happened_at']);
        $this->datas = $array;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge([
            '_app' => $this->getApp(),
            '_host' => $this->getHost(),
            '_happened_at' => $this->getHappenedAt(),
        ], $this->getDatas());
    }
}
