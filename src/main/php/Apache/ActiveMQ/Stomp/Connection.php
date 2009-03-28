<?php

/**
 * The MIT License
 * 
 * Copyright (c) 2009 Ian Zepp
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * @author Ian Zepp
 * @package 
 */

/**
 *
 * @author Ian Zepp
 */
class Apache_ActiveMQ_Stomp_Connection {
	/**
	 *
	 * @return string
	 */
	public function __toString () {
		return get_class ($this);
	}
	
	/**
	 * Default status when the resource is closed or uninitialized
	 */
	const STATUS_CLOSED = 0x00;
	
	/**
	 * Resource is open, but not yet authorized to pass messages
	 */
	const STATUS_OPEN = 0x10;
	
	/**
	 * Resource is fully connected and authorized
	 */
	const STATUS_CONNECTED = 0x20;
	
	/**
	 * @var Apache_ActiveMQ_Stomp_Broker
	 */
	private $broker;
	
	/**
	 * @var resource
	 */
	private $connection;
	
	/**
	 * Enter description here...
	 *
	 * @var integer In milliseconds.
	 */
	private $connectionTimeout = PHP_INT_MAX;
	
	/**
	 * @var Apache_ActiveMQ_Stomp_Session
	 */
	private $session;
	
	/**
	 * Enter description here...
	 *
	 * @var array(Apache_ActiveMQ_Stomp_Frame)
	 */
	private $pendingFrames = array ();
	
	/**
	 * Enter description here...
	 *
	 * @var integer In milliseconds.
	 */
	private $readTimeout = PHP_INT_MAX;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $readBuffer;
	
	/**
	 * Enter description here...
	 *
	 * @var integer Defaults to 2048
	 */
	private $readSize = 2048;
	
	/**
	 * @var integer
	 */
	private $status = self::STATUS_CLOSED;
	
	/**
	 * Disconnect the remote end
	 */
	public function __destruct () {
		try {
			$this->disconnect ();
		} catch (Exception $e) {
			$message = "{$this}::__destruct(): Exception '{$e->getMessage ()}':\n";
			$message .= $e->getTraceAsString ();
			file_put_contents ("logging://warn", $message);
		}
	}
	
	/**
	 * @param Apache_ActiveMQ_Stomp_Frame $pendingFrame
	 */
	public function appendToPendingFrames (Apache_ActiveMQ_Stomp_Frame $pendingFrame) {
		$this->pendingFrames [] = $pendingFrame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $data
	 */
	public function appendToReadBuffer ($data) {
		assert (is_string ($data));
		$this->readBuffer .= strval ($data);
	}
	
	/**
	 * @return Apache_ActiveMQ_Stomp_Broker
	 */
	public function getBroker () {
		return $this->broker;
	}
	
	/**
	 * Get the connection resource, suitable for use with fgets or fputs
	 *
	 * @return resource
	 */
	public function getConnection () {
		return $this->connection;
	}
	
	/**
	 * @return integer
	 */
	public function getConnectionTimeout () {
		return $this->connectionTimeout;
	}
	
	/**
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function getPendingFrame () {
		return array_shift ($this->pendingFrames);
	}
	
	/**
	 * @return string
	 */
	public function getReadBuffer () {
		return $this->readBuffer;
	}
	
	/**
	 * @return integer
	 */
	public function getReadBufferSize () {
		return is_string ($this->readBuffer) ? strlen ($this->readBuffer) : 0;
	}
	
	/**
	 * @return integer
	 */
	public function getReadSize () {
		return $this->readSize;
	}
	
	/**
	 * @return integer
	 */
	public function getReadTimeout () {
		return $this->readTimeout;
	}
	
	/**
	 * @return Apache_ActiveMQ_Stomp_Session
	 */
	public function getSession () {
		return $this->session;
	}
	
	/**
	 * @return integer
	 */
	public function getStatus () {
		return $this->status;
	}
	
	/**
	 * @return boolean
	 */
	public function hasPendingFrames () {
		return count ($this->pendingFrames) > 0;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $brokerUri
	 * @return Apache_ActiveMQ_Stomp_Broker
	 */
	public function newBroker ($brokerUri = null) {
		assert (is_string ($brokerUri) || is_null ($brokerUri));
		assert (file_put_contents ("logging://debug", "{$this}::newBroker({$brokerUri})"));
		return new Apache_ActiveMQ_Stomp_Broker ($brokerUri);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newConnectFrame () {
		$frame = $this->newFrame ("CONNECT");
		$frame->setHeader ("login", $this->getBroker ()->getUsername ());
		$frame->setHeader ("passcode", $this->getBroker ()->getPassword ());
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newDisconnectFrame () {
		return $this->newFrame ("DISCONNECT");
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $message
	 * @return Apache_ActiveMQ_Exception
	 */
	public function newException ($message = null) {
		return new Apache_ActiveMQ_Exception ($message);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $command
	 * @param string $messageData
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newFrame ($command = null, $messageData = null) {
		assert (is_string ($command) || is_null ($command));
		assert (is_string ($messageData) || is_null ($messageData));
		return new Apache_ActiveMQ_Stomp_Frame ($command, $messageData);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @param string|null $messageData
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newMessageFrame ($endpoint, $messageData = null) {
		assert (is_string ($endpoint));
		assert (is_string ($messageData) || is_null ($messageData));
		
		$frame = $this->newFrame ("SEND", $messageData);
		$frame->setHeader ("destination", $endpoint);
		$frame->setHeader ("reply-to", "{$endpoint}.response");
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @param string $correlationId
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newResponseSubscriptionFrame ($endpoint, $correlationId) {
		assert (is_string ($endpoint));
		assert (is_string ($correlationId));
		
		$frame = $this->newSubscriptionFrame ("{$endpoint}.response");
		$frame->setHeader ("id", strval ($correlationId));
		$frame->setHeader ("selector", "JMSCorrelationID='{$correlationId}'");
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $sessionId
	 * @return Apache_ActiveMQ_Stomp_Session
	 */
	public function newSession ($sessionId = null) {
		assert (is_string ($sessionId) || is_null ($sessionId));
		return new Apache_ActiveMQ_Stomp_Session ($sessionId);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newSubscriptionFrame ($endpoint) {
		assert (is_string ($endpoint));
		$frame = $this->newFrame ("SUBSCRIBE");
		$frame->setHeader ("destination", $endpoint);
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $message
	 * @return Apache_ActiveMQ_Exception
	 */
	public function newTimeoutException ($message = null) {
		return new Apache_ActiveMQ_Stomp_TimeoutException ($message);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newUnsubscriptionFrame ($correlationId) {
		assert (is_string ($correlationId));
		
		$frame = $this->newFrame ("UNSUBSCRIBE");
		$frame->setHeader ("id", $correlationId);
		return $frame;
	}
	
	/**
	 * @param Apache_ActiveMQ_Stomp_Broker $brokerPass
	 */
	public function setBroker (Apache_ActiveMQ_Stomp_Broker $broker) {
		assert (file_put_contents ("logging://debug", "{$this}::setBroker({$broker})"));
		$this->broker = $broker;
	}
	
	/**
	 * @param string $brokerUri
	 * @throws Apache_ActiveMQ_Exception If the broker uri is missing a valid host
	 */
	public function setBrokerUri ($brokerUri) {
		assert (is_string ($brokerUri));
		assert (file_put_contents ("logging://debug", "{$this}::setBrokerUri({$brokerUri})"));
		$this->setBroker ($this->newBroker ($brokerUri));
	}
	
	/**
	 * @param resource|null $connection
	 */
	public function setConnection ($connection) {
		assert (is_resource ($connection) || is_null ($connection));
		assert (file_put_contents ("logging://debug", "{$this}::setConnection({$connection})"));
		$this->connection = $connection;
	}
	
	/**
	 * @param integer $connectionTimeout
	 */
	public function setConnectionTimeout ($connectionTimeout) {
		assert (is_integer ($connectionTimeout) || preg_match ("/^[0-9]+$/", $connectionTimeout));
		assert (file_put_contents ("logging://debug", "{$this}::setConnectionTimeout({$connectionTimeout})"));
		$this->connectionTimeout = intval ($connectionTimeout);
	}
	
	/**
	 * @param string|null $readBuffer
	 */
	public function setReadBuffer ($readBuffer) {
		assert (is_string ($readBuffer) || is_null ($readBuffer));
		assert (file_put_contents ("logging://debug", "{$this}::setReadBuffer({$readBuffer})"));
		$this->readBuffer = $readBuffer;
	}
	
	/**
	 * @param integer $readSize
	 */
	public function setReadSize ($readSize) {
		assert (is_integer ($readSize) || preg_match ("/^[0-9]+$/", $readSize));
		assert (file_put_contents ("logging://debug", "{$this}::setReadSize({$readSize})"));
		$this->readSize = intval ($readSize);
	}
	
	/**
	 * @param integer $readTimeout
	 */
	public function setReadTimeout ($readTimeout) {
		assert (is_integer ($readTimeout) || preg_match ("/^[0-9]+$/", $readTimeout));
		assert (file_put_contents ("logging://debug", "{$this}::setReadTimeout({$readTimeout})"));
		$this->readTimeout = $readTimeout;
	}
	
	/**
	 * @param Apache_ActiveMQ_Stomp_Session $session
	 */
	public function setSession (Apache_ActiveMQ_Stomp_Session $session) {
		assert (file_put_contents ("logging://debug", "{$this}::setSession({$session})"));
		$this->session = $session;
	}
	
	/**
	 * @param integer $status
	 */
	public function setStatus ($status) {
		assert (is_integer ($status));
		assert (file_put_contents ("logging://debug", "{$this}::setStatus({$status})"));
		$this->status = $status;
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function connect ($force = false) {
		assert (is_bool ($force));
		assert (file_put_contents ("logging://debug", "{$this}::connect({$force})"));
		
		if ($this->getStatus () > self::STATUS_CLOSED && $force == false) {
			throw $this->newException ("Illegal to attempt a new connection when already connected. Call disconnect() first. ");
		} elseif ($this->getStatus ()) {
			$this->disconnect ();
		}
		
		// Does the broker exist?
		if ($this->getBroker () == null) {
			throw $this->newException ("No broker has been set. Call setBrokerUri() first.");
		}
		
		// Attempt the connection
		$errnum = 0;
		$errstr = '';
		$connectionTimeout = ((double) $this->getConnectionTimeout ());
		$resourceUri = $this->getBroker ()->getProtocol () . '://' . $this->getBroker ()->getHost ();
		$resource = fsockopen ($resourceUri, $this->getBroker ()->getPort (), $errnum, $errstr, $connectionTimeout);
		
		if (is_resource ($resource) == false) {
			throw $this->newException ("Failed to connect to the broker [error #{$errnum}][{$errstr}] for {$this->getBroker ()->getUri ()}");
		}
		
		// Save the resource and initialize a session
		$this->setConnection ($resource);
		$this->setStatus (self::STATUS_OPEN);
		$this->setSession ($this->newSession ());
		
		// Send the frame
		$this->sendFrame ($this->newConnectFrame ());
		
		// Read in a response (better be the only frame in there)
		$reply = $this->readFrame ();
		
		if ($reply->getCommand () == "CONNECTED") {
			$this->getSession ()->setSessionId (array_shift ($reply->getHeaders ("session")));
			$this->setStatus (self::STATUS_CONNECTED);
		} else {
			$this->disconnect ();
			throw $this->newException ("Unexpected response: {$reply->getCommand ()}");
		}
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function disconnect () {
		assert (file_put_contents ("logging://debug", "{$this}::disconnect()"));
		
		if ($this->getStatus () >= self::STATUS_OPEN) {
			$this->sendFrame ($this->newDisconnectFrame (), false);
		}
		
		if ($this->getConnection () != null) {
			fclose ($this->getConnection ());
		}
		if ($this->getSession () != null) {
			$this->getSession ()->setSessionId (null);
		}
		
		$this->setStatus (self::STATUS_CLOSED);
		$this->setConnection (null);
	}
	
	/**
	 * @param Apache_ActiveMQ_Stomp_Frame $frame
	 * @param boolean $synchronous Defaults to true
	 * @return string The unique messageId
	 * @throws Apache_ActiveMQ_Exception
	 */
	public function sendFrame (Apache_ActiveMQ_Stomp_Frame $frame) {
		assert (file_put_contents ("logging://debug", "{$this}::sendFrame({$frame})"));
		
		if ($this->getStatus () < self::STATUS_OPEN) {
			$message = "{$this}::sendFrame(): connection status error, ";
			$message .= "Illegal to attempt a send a message without an open resource. Call connect() first.";
			throw $this->newException ($message);
		}
		if ($this->getStatus () < self::STATUS_CONNECTED && $frame->getCommand () != "CONNECT") {
			throw $this->newException ("Illegal to attempt a send a message when not yet authorized.");
		}
		
		// Write the data to the socket
		$frameData = $frame->getEncapsulatedData ();
		$frameSize = fputs ($this->getConnection (), $frameData);
		
		if ($frameSize === false) {
			$message = "{$this}::sendFrame(): stream write error, ";
			$message .= "Unable to write message data to connection resource";
			throw $this->newException ($message);
		}
		
		if ($frameSize !== strlen ($frameData)) {
			$message = "{$this}::sendFrame(): stream write error, ";
			$message .= "expected '" . strlen ($frameData) . "' bytes written, ";
			$message .= "but only was able to write '{$frameSize}' bytes";
			throw $this->newException ($message);
		}
		
		// Debugging
		if (ASSERT_ACTIVE) {
			$map ["message"] = "Processed frame";
			$map ["frame"] = $frame;
			$map ["this"] = $this;
			file_put_contents("logging://debug", print_r($map, true));
		}
		
		// Stats
		$this->getSession ()->incrementSendFrames ();
		$this->getSession ()->incrementSendBytes ($frameSize);
		
		// Return the message id
		return $frame->getCorrelationId ();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $messageData
	 * @param string $endpoint
	 * @return string The unique messageId
	 */
	public function sendMessage ($endpoint, $messageData) {
		assert (is_string ($endpoint));
		assert (is_string ($messageData));
		return $this->sendFrame ($this->newMessageFrame ($endpoint, $messageData));
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @param string $messageData
	 * @return string
	 */
	public function sendRequestResponse ($endpoint, $messageData) {
		assert (is_string ($endpoint));
		assert (is_string ($messageData));
		
		$frame = $this->newMessageFrame ($endpoint, $messageData);
		
		// Build the subscription message
		$subscription = $this->newResponseSubscriptionFrame ($endpoint, $frame->getCorrelationId ());
		
		// Send the request messages
		$this->sendFrame ($subscription);
		$this->sendFrame ($frame);
		
		// Read until we get a correlated message
		$reply = $this->readCorrelatedFrame ($frame);
		
		// Unsubscribe from the response channel
		$this->sendFrame ($this->newUnsubscriptionFrame ($frame->getCorrelationId ()));
		
		// Return the response data
		return $reply->getBody ();
	}
	
	/**
	 * Reads a message
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 * @throws Apache_ActiveMQ_Exception If a read timeout occurs
	 */
	public function readFrame () {
		// If there are frames in the buffer, get them first
		if ($this->hasPendingFrames ()) {
			return $this->getPendingFrame ();
		}
		
		// Find at least the headers
		while (($headerLength = strpos ($this->getReadBuffer (), "\n\n")) === false) {
			$this->readIntoBuffer ();
		}
		
		// Debugging
		$loggingMessage = "{$this}::readFrame(): Header length of '{$headerLength}' bytes";
		assert (file_put_contents ("logging://debug", $loggingMessage));
		
		// Dirty match to the content-length. If the content-length header is present,
		// then make sure we have at least that many bytes in the readBuffer before
		// attempting to process the frame.
		//
		// Otherwise, make sure we have at least one null byte before processing.
		$matches = array ();
		
		if (preg_match ("/content-length:(.+?)\n/", $this->getReadBuffer (), $matches)) {
			$contentLength = intval ($matches [1]);
			
			// Debugging
			$loggingMessage = "{$this}::readFrame(): Content length of '{$contentLength}' bytes";
			assert (file_put_contents ("logging://debug", $loggingMessage));
			
			// Set the buffer minimum
			$bufferMinimum = $headerLength + $contentLength;
			$bufferMinimum += 2; // The "\n\n" characters between header and body.
			

			// Ensure at least that much is in the buffer
			while ($this->getReadBufferSize () < $bufferMinimum) {
				$this->readIntoBuffer ();
			}
		} else {
			// Debugging
			$loggingMessage = "{$this}::readFrame(): No 'content-length' found, reading to first null byte";
			assert (file_put_contents ("logging://debug", $loggingMessage));
			
			while (($bufferMinimum = strpos ($this->getReadBuffer (), Apache_ActiveMQ_Stomp_Frame::TERMINATOR)) === false) {
				$this->readIntoBuffer ();
			}
		}
		
		// Debugging
		$loggingMessage = "{$this}::readFrame(): Buffer minimum of '{$bufferMinimum}' bytes";
		assert (file_put_contents ("logging://debug", $loggingMessage));
		
		// Extract the buffer minimum
		$frameData = substr ($this->getReadBuffer (), 0, $bufferMinimum);
		$frameSize = strlen ($frameData);
		
		// Debugging
		$loggingMessage = "{$this}::readFrame(): read raw data  of '{$frameSize}' bytes";
		// $loggingMessage.= ": " . print_r ($frameData, true);
		assert (file_put_contents ("logging://debug", $loggingMessage));
		
		// Replace the buffer data (skipping the trailing terminator)
		$this->setReadBuffer (substr ($this->getReadBuffer (), $bufferMinimum + strlen (Apache_ActiveMQ_Stomp_Frame::TERMINATOR)));
		
		// Extract the header array and the body data
		list ($headers, $body) = explode ("\n\n", $frameData, 2);
		$headers = explode ("\n", $headers);
		
		// Create the frame and set the command data
		$frame = $this->newFrame (array_shift ($headers));
		
		// Add in each header
		foreach ($headers as $header) {
			list ($key, $value) = explode (":", $header, 2);
			$frame->setHeader (trim ($key), trim ($value));
		}
		
		// Add the body contents
		$frame->setBody ($body);
		
		// Debug
		$loggingMessage = "{$this}::readFrame(): processed frame: ";
		$loggingMessage .= print_r ($frame, true);
		assert (file_put_contents ("logging://debug", $loggingMessage));
		
		$this->getSession ()->incrementReadFrames ();
		$this->getSession ()->incrementReadBytes ($frameSize);
		
		// Done
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param Apache_ActiveMQ_Stomp_Frame $correlatedFrame
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function readCorrelatedFrame (Apache_ActiveMQ_Stomp_Frame $correlatedFrame) {
		$correlationId = $correlatedFrame->getCorrelationId ();
		$currentFrame = null;
		
		// Read until we get a related frame
		do {
			// If the frame is unrelated, skip over it.
			if ($currentFrame instanceof Apache_ActiveMQ_Stomp_Frame) {
				$this->appendToPendingFrames ($currentFrame);
			}
			
			$currentFrame = $this->readFrame ();
		} while ($currentFrame->getCorrelationId () != $correlationId);
		
		return $currentFrame;
	}
	
	/**
	 * Enter description here...
	 */
	public function readIntoBuffer () {
		if ($this->getReadBufferWaiting () === false) {
			throw $this->newTimeoutException ();
		}
		
		$read = fgets ($this->getConnection (), $this->getReadSize ());
		
		if ($read === false) {
			throw $this->newException ("Stream read error occurred");
		}
		
		$this->appendToReadBuffer ($read);
	}
	
	/**
	 * Tests the resource stream to see if there is available data in the read buffer
	 *
	 * @param integer $readTimeout
	 * @return boolean
	 * @throws Apache_ActiveMQ_Exception If the read resource is unavailable for any reason
	 */
	public function getReadBufferWaiting ($readTimeout = null) {
		assert (is_integer ($readTimeout) || is_null ($readTimeout));
		
		if ($this->getStatus () == self::STATUS_CLOSED) {
			throw $this->newException ("Connection is closed");
		}
		if ($readTimeout == null) {
			$readTimeout = $this->getReadTimeout ();
		}
		
		$read = array ($this->getConnection ());
		$send = null;
		$else = null;
		
		// Debug
		$loggingMessage = "{$this}::getReadBufferWaiting({$readTimeout})";
		assert (file_put_contents ("logging://debug", $loggingMessage));
		
		// Read the stream
		$waiting = stream_select ($read, $send, $else, $readTimeout, 0);
		
		if ($waiting === false) {
			throw $this->newException ("Stream select error, unable to determine if resource is readable");
		} else {
			return $waiting > 0;
		}
	}
}

