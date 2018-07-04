<?php

namespace Rcason\MqAmqp\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use Rcason\Mq\Api\Config\ConfigInterface as QueueConfig;
use Rcason\MqAmqp\Model\Client;

class Recurring implements InstallSchemaInterface
{
    const QUEUE_PASSIVE = false;
    const QUEUE_DURABLE = true;
    const QUEUE_EXCLUSIVE = false;
    const QUEUE_AUTO_DELETE = false;
    const EXCHANGE_TYPE = 'direct';
    
    /**
     * @var QueueConfig
     */
    private $queueConfig;
    
    /**
     * @var Client
     */
    private $client;

    /**
     * @param QueueConfig $queueConfig
     * @param Client $client
     */
    public function __construct(
        QueueConfig $queueConfig,
        Client $client
    ) {
        $this->queueConfig = $queueConfig;
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // Skip queues setup if no AMQP queue is declares
        $queues = $this->getAmqpQueues();
        if(count($queues) == 0) {
            return;
        }
        
        // Open AMQP connection and channel
        $channel = $this->client->getChannel();
        
        // Declare queues and exchanges
        foreach($queues as $queue) {
            // Create non passive, durable non exclusive queue with no auto delete
            $channel->queue_declare(
                $queue,
                self::QUEUE_PASSIVE,
                self::QUEUE_DURABLE,
                self::QUEUE_EXCLUSIVE,
                self::QUEUE_AUTO_DELETE
            );
            
            // Create direct exchange, non passive, durable and with no auto delete
            $channel->exchange_declare(
                $queue,
                self::EXCHANGE_TYPE,
                self::QUEUE_PASSIVE,
                self::QUEUE_DURABLE,
                self::QUEUE_AUTO_DELETE
            );
            
            // Bind queue to exchange
            $channel->queue_bind($queue, $queue);
        }
    }
    
    /**
     * Return the AMQP queues
     * 
     * @return string[]
     */
    private function getAmqpQueues()
    {
        $queueConfig = $this->queueConfig;
        if (!$this->queueConfig || !$this->queueConfig->getQueueNames()) {
            return [];
        }
        
        return array_filter($queueConfig->getQueueNames(), function($name) use($queueConfig) {
            return $queueConfig->getQueueBroker($name) == \Rcason\MqAmqp\Model\AmqpBroker::BROKER_CODE;
        });
    }
}
