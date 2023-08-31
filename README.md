# apache-activemq-stomp

Note: project was originally written in 2008/2009, saved in Google Code until the service was shut down, and then archived to Github.

## Overview

This project is an integration effort at bringing the full power of Apache ActiveMQ to the PHP platform. It uses the text-based Stomp protocol to provide full messaging under the following patterns:

async send
async send/receive
sync send
sync send/receive
Using this is (almost) as simple as:

```php
$connection = new Apache_ActiveMQ_Stomp_Connection;
$connection->setBrokerUri ("tcp://localhost:61613");
$connection->connect ();

// Send an asynchronous message
$connection->sendMessage ("/topic/testing.1", "Hello World!");

// Send an XML snippet, and get a response $request = "";
$response = $connection->sendRequestResponse ("/queue/accounts.find", $request);

// The response now contains the body that the remote service sent back. Notice // that we don't have to do any temporary queue management!

```

http://code.google.com/p/ianzepp/source/browse/trunk/apache-activemq-stomp SVN Source Tree

http://activemq.apache.org/ External: Apache ActiveMQ

http://activemq.apache.org/stomp.html External: Apache ActiveMQ Stomp Protocol
