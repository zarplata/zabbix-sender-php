# Zabbix sender

Zabbix sender it's a PHP implementation of Zabbix sender protocol.
With that library you can send any metric to Zabbix server.
Additional information about Zabbix sender protocol and request/response
you can be found in official documentation:
    - https://www.zabbix.com/documentation/3.4/manual/appendix/items/trapper
    - https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen

### Installation
```sh
composer require zarplata/zabbix-sender
```

### Usage
```php
<?php

use \Zarplata\Zabbix\ZabbixSender;
use \Zarplata\Zabbix\Request\Packet as ZabbixPacket;
use \Zarplata\Zabbix\Request\Metric as ZabbixMetric;

// At first you must initialize ZabbixSender object
// with address of Zabbix Server. If your Zabbix Server
// don't listen default port (10051) you can define it in constructor
// $sender = new ZabbixSender(
//     $serverAddress='ZABBIX_SERVER_HOSTNAME',
//     $serverPort=12345
// );
$sender = new ZabbixSender('ZABBIX_SERVER_HOSTNAME');

// After you define the $sender you must create ZabbixPacket
// it's just accumulator of your metrics which you will add.
$packet = new ZabbixPacket();

// Define your metrinc
$packet->addMetric(new ZabbixMetric('my.super.text.item.key', 'OK'));
$packet->addMetric(new ZabbixMetric('my.super.int.item.key', 1));

// And finally send to Zabbix Server
$sender->send($packet);
```

### Advanced usage options

Sometimes it may be necessary to provide hostname and/or timestamp 
of metric. By default construction:
```php
<?php

new ZabbixMetric('my.super.text.item.key', 'OK');
```
take your current hostname and set object creation time as a metric timestamp.
If you want define another hostname or/and timestamp you must
write the following code:
```php
<?php

(new ZabbixMetric('my.super.text.item.key', 'OK'))
    ->withHostname('my_non_local_hostname')
    ->withTimestamp(662637600); //Timestamp in past 
```

### License

MIT.
