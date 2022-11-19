<?php

declare(strict_types=1);

namespace Mustafa\Messagebus\Consumer;

use Mustafa\Messagebus;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ProcessInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Engine\Channel;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Process\AbstractProcess as SwooleAbstractProcess;
use Hyperf\Process\Event\AfterProcessHandle;
use Hyperf\Process\Event\BeforeProcessHandle;
use Hyperf\Process\Event\PipeMessage;
use Hyperf\Process\Exception\SocketAcceptException;
use Hyperf\Process\ProcessCollector;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\Process as SwooleProcess;
use Swoole\Timer;
use Throwable;

abstract class AbstractProcess extends SwooleAbstractProcess implements ProcessInterface
{
    public string $modelName = 'default'; // 消息队列所属模块

    public string $name = ''; // 进程名称 子类可以重写

    public int $nums = 1; // 进程数量 子类可以重写

    protected int $restartInterval = 5; // 进程重启间隔

    protected string $defaultDriver; // 当前使用的驱动 kafka or redis

    /* @phpstan-ignore-next-line */
    protected array $currentDriverConfig; // 当前驱动的配置信息

    protected ?EventDispatcherInterface $event = null;

    private string $currentConsumerName;

    /* @phpstan-ignore-next-line */
    public array $currentConsumerMessages;

    public function __construct(protected ContainerInterface $container, protected ConfigInterface $config)
    {
        if ($container->has(EventDispatcherInterface::class)) {
            $this->event = $container->get(EventDispatcherInterface::class);
        }
        $this->defaultDriver = $this->config->get('messagebus.default.default_driver');
        if (!$this->name) {
            $this->name = $this->modelName . '-' . $this->defaultDriver . '-consumer';
        }
    }

    public function isEnable($server): bool
    {
        if (!$this->defaultDriver) {
            return false;
        }
        $this->currentDriverConfig = $this->config->get('messagebus.' . $this->modelName . '.drivers.' . $this->defaultDriver);
        if (!$this->currentDriverConfig) {
            return false;
        }

        return true;
    }

    public function handle(): void
    {
        foreach ($this->currentConsumerMessages as $message) {
            try {
                /* @phpstan-ignore-next-line */
                $this->event && $this->event->dispatch(
                    new MessageBus\Consumer\Events\BeforeProcessHandle(
                        $this,
                        $this->currentConsumerName,
                        $message
                    )
                );
                $this->handleMessage(
                    $this->currentConsumerName,
                    $message->headers->getEvent(),
                    $message->payload->toArray()
                );
            } catch (\Exception $e){

            } finally {
                /* @phpstan-ignore-next-line */
                $this->event && $this->event->dispatch(
                    new MessageBus\Consumer\Events\AfterProcessHandle(
                        $this,
                        $this->currentConsumerName,
                        $message
                    )
                );
            }
        }
    }

    public function bind($server): void
    {
        $consumers = $this->getConsumersWithTopicsAndEvents();

        $this->beforeMessageBus();

        $consumerPool = null;
        if ($this->defaultDriver === MessageBus\Constants\MessageBusType::KAFKA) {
            $consumerPool = make(MessageBus\Consumer\Kafka\KafkaConsumerPool::class, [
                $this->modelName,
                $consumers,
                $this->currentDriverConfig,
            ]);
        }

        $num = $this->nums;
        for ($i = 0; $i < $num; ++$i) {
            $process = new SwooleProcess(function (SwooleProcess $process) use ($consumerPool, $consumers, $i) {
                try {
                    /* @phpstan-ignore-next-line */
                    $this->event && $this->event->dispatch(new BeforeProcessHandle($this, $i));

                    $this->process = $process;
                    $quit = new Channel(1);
                    $this->listen($quit);
                    if ($consumerPool) {
                        $consumer = $consumerPool->get();
                    } elseif ($this->defaultDriver === MessageBus\Constants\MessageBusType::REDIS) {
                        $consumer = make(MessageBus\Consumer\Redis\RedisConsumerImplements::class, [
                            $consumers, $this->modelName,
                        ]);
                    }

                    if (!isset($consumer)) {
                        echo 'get connection failed' . PHP_EOL;
                        return;
                    }

                    // 拿消息
                    while (ProcessManager::isRunning()) {
                        $this->currentConsumerName = '';
                        foreach ($consumers as $consumer_name => $consumer_cfg) {
                            $events = $consumer_cfg['events'];
                            $this->currentConsumerMessages = $consumer->consume($consumer_name, $events);
                            $this->currentConsumerName = $consumer_name;
                            $this->handle();
                            $consumer->commit();
                        }
                        sleep($this->config->get('messagebus.' . $this->modelName . '.consumer_interval'));
                    }
                } catch (Throwable $throwable) {
                    $this->logThrowable($throwable);
                } finally {
                    $this->event && $this->event->dispatch(new AfterProcessHandle($this, $i));
                    Timer::clearAll();
                    CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
                    sleep($this->restartInterval);
                }
            }, false, SOCK_DGRAM, true);

            /* @phpstan-ignore-next-line */
            $server->addProcess($process);

            ProcessCollector::add($this->name, $process);
        }
    }

    /**
     * 获取监听的消费者列表对应的topic和关注的事件
     * 返回格式如
     * [
     *  'ruby-live25' => [ // 消费者消费的topic
     *      'topics' => [
     *          "topic1",
     *          "topic2"
     *      ],
     *      'events' => [ // 消费者关注的事件
     *          "events1",
     *          "events2",
     *      ],
     *  ],
     * ],
     * 具体数据可以从配置中心数据库等来源获得.
     * @return array
     */
    abstract protected function getConsumersWithTopicsAndEvents(): array;

    /**
     * 处理接受到的消息.
     * @param string $consumer 消费者
     * @param string $event 事件名称
     * @param array $payload 事件携带内容
     */
    abstract protected function handleMessage(string $consumer, string $event, array $payload): void;

    abstract protected function beforeMessageBus();

    /**
     * Added event for listening data from worker/task.
     */
    protected function listen(Channel $quit)
    {
        \Hyperf\Utils\Coroutine::create(function () use ($quit) {
            while ($quit->pop(0.001) !== true) {
                try {
                    /** @var \Swoole\Coroutine\Socket $sock */
                    $sock = $this->process->exportSocket();
                    $recv = $sock->recv(65535, 0.2);
                    if ($recv === '') {
                        throw new SocketAcceptException('Socket is closed', $sock->errCode);
                    }

                    if ($recv === false && $sock->errCode !== SOCKET_ETIMEDOUT) {
                        throw new SocketAcceptException('Socket is closed', $sock->errCode);
                    }

                    if ($this->event && $recv !== false && $data = unserialize($recv)) {
                        $this->event->dispatch(new PipeMessage($data));
                    }
                } catch (Throwable $exception) {
                    $this->logThrowable($exception);
                    if ($exception instanceof SocketAcceptException) {
                        // TODO: Reconnect the socket.
                        break;
                    }
                }
            }
            $quit->close();
        });
    }

    protected function logThrowable(Throwable $throwable): void
    {
        if ($this->container->has(StdoutLoggerInterface::class) && $this->container->has(FormatterInterface::class)) {
            $logger = $this->container->get(StdoutLoggerInterface::class);
            $formatter = $this->container->get(FormatterInterface::class);
            $logger->error($formatter->format($throwable));

            if ($throwable instanceof SocketAcceptException) {
                $logger->critical('Socket of process is unavailable, please restart the server');
            }
        }
    }
}
