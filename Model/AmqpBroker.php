<?php

namespace Rcason\MqAmqp\Model;

use PhpAmqpLib\Message\AMQPMessage;
use Rcason\Mq\Api\Data\MessageEnvelopeInterface;
use Rcason\Mq\Api\Data\MessageEnvelopeInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AmqpBroker implements \Rcason\Mq\Api\BrokerInterface
{
    const BROKER_CODE = 'amqp';
    
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var MessageEnvelopeInterfaceFactory
     */
    private $messageEnvelopeFactory;
    
    /**
     * @var string
     */
    private $queueName;
    
    /**
     * @param Client $client
     */
    public function __construct(
        Client $client,
        MessageEnvelopeInterfaceFactory $messageEnvelopeFactory,
        $queueName = null
    ) {
        $this->client = $client;
        $this->messageEnvelopeFactory = $messageEnvelopeFactory;
        $this->queueName = $queueName;
    }
    
    /**
     * @inheritdoc
     */
    public function enqueue(MessageEnvelopeInterface $messageEnvelope)
    {
        // Open connection and get channel
        $channel = $this->client->getChannel();
        
        // Prepare message
        $message = new AMQPMessage(
            $messageEnvelope->getContent(), [
                'content_type' => $messageEnvelope->getContentType(),
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );
        
        // Send message to exchange
        $channel->basic_publish($message, $this->queueName);
    }
    
    /**
     * @inheritdoc
     */
    public function peek()
    {
        // Open connection and get channel
        $channel = $this->client->getChannel();
        
        // Get message
        $message = $channel->basic_get($this->queueName);
        if(!$message) {
            return false;
        }
        
        // Return message in envelope
        return $this->messageEnvelopeFactory->create()
            ->setBrokerRef($message->delivery_info['delivery_tag'])
            ->setContent($message->body);
    }
    
    /**
     * @inheritdoc
     */
    public function acknowledge(MessageEnvelopeInterface $message)
    {
        // Get channel
        $channel = $this->client->getChannel();
        
        // Send ACK
        $channel->basic_ack($message->getBrokerRef());
    }
    
    /**
     * @inheritdoc
     */
    public function reject(MessageEnvelopeInterface $message, bool $requeue, int $maxRetries, int $retryInterval)
    {
        // Get channel
        $channel = $this->client->getChannel();
        
        // Send Reject
        $channel->basic_reject($message->getBrokerRef(), $requeue);
    }
}
