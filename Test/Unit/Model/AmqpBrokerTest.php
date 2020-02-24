<?php

namespace Rcason\MqAmqp\Test\Unit\Model;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Rcason\Mq\Api\BrokerInterface;
use Rcason\Mq\Api\Data\MessageEnvelopeInterfaceFactory;
use Rcason\Mq\Model\MessageEnvelope;
use Rcason\MqAmqp\Model\AmqpBroker;
use Rcason\MqAmqp\Model\Client;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AmqpBrokerTest extends \PHPUnit\Framework\TestCase
{
    const QUEUE_NAME = 'test_queue';
    const MESSAGE_ID = 294;
    const MESSAGE_CONTENT = 'Message content';
    const MESSAGE_CONTENT_TYPE = 'text/plain';
    
    /**
     * @var MessageEnvelopeInterfaceFactory|MockObject
     */
    private $messageEnvelopeFactory;
    
    /**
     * @var MessageEnvelope
     */
    private $messageEnvelope;
    
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var AMQPChannel
     */
    private $channel;
    
    /**
     * @var AmqpBroker
     */
    private $amqpBroker;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        
        $this->messageEnvelopeFactory = $this->getMockBuilder(MessageEnvelopeInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        
        $this->messageEnvelope = $objectManager->getObject(MessageEnvelope::class);
        
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->channel = $this->createMock(AMQPChannel::class);
        
        $this->amqpBroker = $objectManager->getObject(AmqpBroker::class, [
            'client' => $this->client,
            'messageEnvelopeFactory' => $this->messageEnvelopeFactory,
            'queueName' => self::QUEUE_NAME,
        ]);
        
        parent::setUp();
    }
    
    public function testServiceContract()
    {
        $this->assertInstanceOf(BrokerInterface::class, $this->amqpBroker);
    }

    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::enqueue
     */
    public function testEnqueue()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
        
        $amqpMessage = new AMQPMessage(
            self::MESSAGE_CONTENT, [
                'content_type' => self::MESSAGE_CONTENT_TYPE,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );
        
        $this->channel->expects($this->once())
            ->method('basic_publish')
            ->with($amqpMessage, self::QUEUE_NAME);
        
        $this->messageEnvelope->setContent(self::MESSAGE_CONTENT);
        $this->messageEnvelope->setContentType(self::MESSAGE_CONTENT_TYPE);
        
        $this->amqpBroker->enqueue($this->messageEnvelope);
    }

    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::peek
     */
    public function testPeek()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
        
        $amqpMessage = new AMQPMessage(
            self::MESSAGE_CONTENT, [
                'content_type' => self::MESSAGE_CONTENT_TYPE,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );
        $amqpMessage->delivery_info['delivery_tag'] = self::MESSAGE_ID;
        
        $this->channel->expects($this->once())
            ->method('basic_get')
            ->with(self::QUEUE_NAME)
            ->willReturn($amqpMessage);
        
        $this->messageEnvelopeFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->messageEnvelope);
        
        $this->assertEquals(
            $this->amqpBroker->peek(),
            $this->messageEnvelope
        );
        
        $this->assertEquals(
            $this->messageEnvelope->getBrokerRef(),
            self::MESSAGE_ID
        );
        
        $this->assertEquals(
            $this->messageEnvelope->getContent(),
            self::MESSAGE_CONTENT
        );
    }
    
    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::peek
     */
    public function testPeekEmptyQueue()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
            
        $this->channel->expects($this->once())
            ->method('basic_get')
            ->with(self::QUEUE_NAME)
            ->willReturn(null);
        
        $this->assertEquals(
            $this->amqpBroker->peek(),
            false
        );
    }

    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::acknowledge
     */
    public function testAcknowledge()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
            
        $this->channel->expects($this->once())
            ->method('basic_ack')
            ->with(self::MESSAGE_ID);
        
        $this->messageEnvelope->setBrokerRef(self::MESSAGE_ID);
        $this->amqpBroker->acknowledge($this->messageEnvelope);
    }

    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::reject
     */
    public function testReject()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
            
        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with(self::MESSAGE_ID, false);
        
        $this->messageEnvelope->setBrokerRef(self::MESSAGE_ID);
        $this->amqpBroker->reject($this->messageEnvelope, false, 5, 0);
    }
    
    /**
     * @covers Rcason\MqAmqp\Model\AmqpBroker::reject
     */
    public function testRejectRequeue()
    {
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
            
        $this->channel->expects($this->once())
            ->method('basic_reject')
            ->with(self::MESSAGE_ID, true);
        
        $this->messageEnvelope->setBrokerRef(self::MESSAGE_ID);
        $this->amqpBroker->reject($this->messageEnvelope, true, 5, 0);
    }
}
