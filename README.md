# Magento 2 Message Queue AMQP Backend
AMQP message queue backend implementation for [Rcason_Mq](https://github.com/renatocason/magento2-module-mq).

## Installation
1. Require the module via Composer
```bash
$ composer require renatocason/magento2-module-mq-amqp
```

2. Enable the module
```bash
$ bin/magento module:enable Rcason_MqAmqp
$ bin/magento setup:upgrade
```

## Configuration
1. Configure the Mq module as explained [here](https://github.com/renatocason/magento2-module-mq)
2. Configure the AMQP connection in your _app/etc/env.php_ file
```php
  'ce_mq' => [
      'amqp' => [
          'host' => 'localhost',
          'port' => 5672,
          'username' => 'guest',
          'password' => 'guest',
          'virtualhost' => '/',
      ],
  ],
```

3. Specify _amqp_ as broker when configuring a queue in your module's _etc/ce_mq.xml_ file
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Rcason_Mq:etc/ce_mq.xsd">
    <ceQueue name="product.updates" broker="amqp"
        messageSchema="int"
        consumerInterface="Rcason\MqExample\Model\ExampleConsumer"/>
</config>
```
4. Run the setup upgrade command each time you edit your queues configuration, as they are applied to the queue manager on a recurring upgrade script
```bash
$ bin/magento setup:upgrade
```

## Authors, contributors and maintainers

Author:
- [Renato Cason](https://github.com/renatocason)

## License
Licensed under the Open Software License version 3.0
