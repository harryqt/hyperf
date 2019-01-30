<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://hyperf.org
 * @document https://wiki.hyperf.org
 * @contact  group@hyperf.org
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Amqp;

use Hyperf\Amqp\Message\MessageInterface;
use Hyperf\Amqp\Pool\AmqpConnectionPool;
use Hyperf\Amqp\Pool\PoolFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Container\ContainerInterface;

class Builder
{
    protected $name = 'default';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PoolFactory
     */
    private $poolFactory;

    public function __construct(ContainerInterface $container, PoolFactory $poolFactory)
    {
        $this->container = $container;
        $this->poolFactory = $poolFactory;
    }

    /**
     * @throws AMQPProtocolChannelException When the channel operation is failed.
     */
    public function declare(MessageInterface $message, ?AMQPChannel $channel = null): void
    {
        if (! $channel) {
            $pool = $this->getConnectionPool($message->getPoolName());
            /** @var \PhpAmqpLib\Connection\AbstractConnection $connection */
            $connection = $pool->get();
            /** @var \PhpAmqpLib\Channel\AMQPChannel $channel */
            $channel = $connection->channel(1);
        }

        $builder = $message->getExchangeBuilder();

        $channel->exchange_declare($builder->getExchange(), $builder->getType(), $builder->isPassive(), $builder->isDurable(), $builder->isAutoDelete(), $builder->isInternal(), $builder->isNowait(), $builder->getArguments(), $builder->getTicket());

        isset($connection) && $pool->release($connection);
    }

    protected function getChannel(string $poolName, ?Connection $conn = null): Channel
    {
        $pool = $this->getChannelPool($poolName);
        return $pool->get();
    }

    protected function getConnection(string $poolName): Connection
    {
        return $this->poolFactory->getConnectionPool($poolName)->get();
    }

    protected function getConnectionPool(string $poolName): AmqpConnectionPool
    {
        return $this->poolFactory->getConnectionPool($poolName);
    }

}
