<?php

namespace Rcason\MqAmqp\Model;

use Magento\Framework\App\DeploymentConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class Client
{
    const CONFIG_CE_MQ = 'ce_mq';
    const CONFIG_AMQP = 'amqp';

    const HOST = 'host';
    const PORT = 'port';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const VIRTUALHOST = 'virtualhost';

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $config;

    /**
     * Configuration example:
     * <code>
     * 'ce_mq' => [
     *         'amqp' => [
     *             'host' => 'localhost',
     *             'port' => 5672,
     *             'username' => 'guest',
     *             'password' => 'guest',
     *             'virtualhost' => '/',
     *         ],
     *     ],
     * </code>
     *
     * @param DeploymentConfig $config
     */
    public function __construct(
        DeploymentConfig $config
    ) {
        $mqConfig = $config->getConfigData(self::CONFIG_CE_MQ);
        $this->config = isset($mqConfig[self::CONFIG_AMQP]) ? $mqConfig[self::CONFIG_AMQP] : [];
    }

    /**
     * Release resources
     *
     * @return void
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Return configuration value
     *
     * @param string $key
     * @return string
     */
    public function getConfigValue($key)
    {
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    /**
     * Return AMQP channel, opening connection if necessary
     *
     * @return AMQPChannel
     */
    public function getChannel()
    {
        if(!$this->connection || !$this->channel) {
            $this->connection = new AMQPStreamConnection(
                $this->getConfigValue(self::HOST),
                $this->getConfigValue(self::PORT),
                $this->getConfigValue(self::USERNAME),
                $this->getConfigValue(self::PASSWORD),
                $this->getConfigValue(self::VIRTUALHOST)
            );
            $this->channel = $this->connection->channel();
        }
        
        return $this->channel;
    }

    /**
     * Close AMQP connection and channel, if open
     */
    protected function closeConnection()
    {
        if($this->channel) {
            $this->channel->close();
            $this->channel = null;
        }
        if($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
