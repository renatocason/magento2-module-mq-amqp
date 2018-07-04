<?php

namespace Rcason\MqAmqp\Test\Unit\Model;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Magento\Framework\App\DeploymentConfig;
use Rcason\MqAmqp\Model\Client;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    const HOST_VALUE = 'localhost';
    const PORT_VALUE = 1234;
    const USERNAME_VALUE = 'user';
    const PASSWORD_VALUE = 'pass';
    const VIRTUALHOST_VALUE = '';
    
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;
    
    /**
     * @var AMQPStreamConnection
     */
    private $streamConnection;
    
    /**
     * @var AMQPChannel
     */
    private $channel;
    
    /**
     * @var Client
     */
    private $client;
    
    /**
     * Return test AMQP configuration
     * 
     * @return array
     */
    private function getAmqpConfiguration()
    {
        return [
            Client::HOST => self::HOST_VALUE,
            Client::PORT => self::PORT_VALUE,
            Client::USERNAME => self::USERNAME_VALUE,
            Client::PASSWORD => self::PASSWORD_VALUE,
            Client::VIRTUALHOST => self::VIRTUALHOST_VALUE,
        ];
    }
    
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        
        $this->streamConnection = $this->createMock(AMQPStreamConnection::class);
        $this->channel = $this->createMock(AMQPChannel::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        
        $this->deploymentConfig->expects($this->once())
            ->method('getConfigData')
            ->with(Client::CONFIG_CE_MQ)
            ->willReturn([Client::CONFIG_AMQP => $this->getAmqpConfiguration()]);
        
        $this->client = $objectManager->getObject(Client::class, [
            'config' => $this->deploymentConfig,
        ]);
        
        parent::setUp();
    }
    
    /**
     * @covers Rcason\MqAmqp\Model\Client::getConfigValue
     */
    public function testGetConfigValue()
    {
        foreach ($this->getAmqpConfiguration() as $key => $value) {
            $this->assertEquals(
                $this->client->getConfigValue($key),
                $value
            );
        }
    }
}
