<?php

namespace Rcason\MqAmqp\Test\Unit\Setup;

use PhpAmqpLib\Channel\AMQPChannel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use Rcason\Mq\Api\Config\ConfigInterface;
use Rcason\MqAmqp\Model\AmqpBroker;
use Rcason\MqAmqp\Model\Client;
use Rcason\MqAmqp\Setup\Recurring;

class RecurringTest extends \PHPUnit\Framework\TestCase
{
    const QUEUE_NAME = 'test_queue';
    
    /**
     * @var ConfigInterface
     */
    private $config;
    
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var SchemaSetupInterface
     */
    private $setup;
    
    /**
     * @var ModuleContextInterface
     */
    private $context;
    
    /**
     * @var AMQPChannel
     */
    private $channel;
    
    /**
     * @var Recurring
     */
    private $recurring;
    
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        
        $this->config = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->setup = $this->getMockForAbstractClass(SchemaSetupInterface::class);
        $this->context = $this->getMockForAbstractClass(ModuleContextInterface::class);
        $this->channel = $this->createMock(AMQPChannel::class);
        
        $this->recurring = $objectManager->getObject(Recurring::class, [
            'queueConfig' => $this->config,
            'client' => $this->client,
        ]);
        
        parent::setUp();
    }
    
    public function testServiceContract()
    {
        $this->assertInstanceOf(InstallSchemaInterface::class, $this->recurring);
    }
    
    /**
     * @covers Rcason\MqAmqp\Setup\Recurring::install
     */
    public function testInstall()
    {
        $this->config->expects($this->any())
            ->method('getQueueNames')
            ->willReturn([self::QUEUE_NAME]);
        
        $this->config->expects($this->once())
            ->method('getQueueBroker')
            ->with(self::QUEUE_NAME)
            ->willReturn(AmqpBroker::BROKER_CODE);
        
        $this->client->expects($this->once())
            ->method('getChannel')
            ->willReturn($this->channel);
            
        $this->channel->expects($this->once())
            ->method('queue_declare')
            ->with(
                self::QUEUE_NAME,
                Recurring::QUEUE_PASSIVE,
                Recurring::QUEUE_DURABLE,
                Recurring::QUEUE_EXCLUSIVE,
                Recurring::QUEUE_AUTO_DELETE
            );
        
        $this->channel->expects($this->once())
            ->method('exchange_declare')
            ->with(
                self::QUEUE_NAME,
                Recurring::EXCHANGE_TYPE,
                Recurring::QUEUE_PASSIVE,
                Recurring::QUEUE_DURABLE,
                Recurring::QUEUE_AUTO_DELETE
            );
        
        $this->channel->expects($this->once())
            ->method('queue_bind')
            ->with(self::QUEUE_NAME, self::QUEUE_NAME);
        
        $this->recurring->install($this->setup, $this->context);
    }
    
    /**
     * @covers Rcason\MqAmqp\Setup\Recurring::install
     */
    public function testInstallNoQueues()
    {
        $this->config->expects($this->once())
            ->method('getQueueNames')
            ->willReturn([]);
        
        $this->recurring->install($this->setup, $this->context);
    }
    
    /**
     * @covers Rcason\MqAmqp\Setup\Recurring::install
     */
    public function testInstallNoAmqpQueues()
    {
        $this->config->expects($this->any())
            ->method('getQueueNames')
            ->willReturn([self::QUEUE_NAME]);
        
        $this->config->expects($this->once())
            ->method('getQueueBroker')
            ->with(self::QUEUE_NAME)
            ->willReturn('mysql');
        
        $this->recurring->install($this->setup, $this->context);
    }
}
