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

class Apache_ActiveMQ_Stomp_Connection {
	/** Observation states */
	const Disconnected = 0;
	const Opening = 1;
	const Open = 2;
	const Establishing = 3;
	const Established = 4;
	
	/** Private variables */
	private $socket;
	private $uri;
	private $receiveBuffer = "";
	private $correlatedFrames = array ();
	private $receiveSize = 0;
	private $state;
	
	/**
	 * Enter description here...
	 *
	 */
	public function __destruct () {
		if ($this->isConnectionEstablished ())
			$this->newDisconnectFrame ()->send ();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function __toString () {
		return get_class ($this);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $message
	 */
	protected function debug ($message) {
		assert (file_put_contents ("php://stderr", "[DEBUG]   : {$message}\n"));
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function connect () {
		$this->debug ("{$this}::connect ()");
		$this->debug ("\t Remote Host: " . $this->getUri ()->getHost ());
		$this->debug ("\t Remote Port: " . $this->getUri ()->getPort ());
		
		if ($this->isConnectionOpen ())
			throw new Apache_ActiveMQ_Stomp_Exception_Connect ("Already in an open-connection state");
		
		try {
			$this->setState (self::Connecting);
			$this->socket = socket_create (AF_INET, SOCK_STREAM, SOL_TCP);
			
			if ($this->socket === false)
				throw new Apache_ActiveMQ_Stomp_Exception_Connect ("Failed to create socket resource");
			if (!socket_connect ($this->socket, $this->getUri ()->getHost (), $this->getUri ()->getPort ()))
				throw new Apache_ActiveMQ_Stomp_Exception_Connect ("Failed to connect to remote host");
			
			$this->setState (self::Connected);
			$this->setState (self::Authenticating);
			$this->newConnectFrame ()->send ();
			
			// Read a response frame (better be the only frame)
			$frame = $this->receiveFrame ();
			
			if (is_null ($frame))
				throw new Apache_ActiveMQ_Stomp_Exception_Connect ("Failed to receive a valid response frame");
			if ($frame->getCommand () != Apache_ActiveMQ_Stomp_Frame::Connected)
				throw new Apache_ActiveMQ_Stomp_Exception_Connect ("Failed to receive connection response frame");
				
			// Done, connected.
			$this->setState (self::Authenticated);
			$this->setState (self::Listening);
		} catch (Exception $exception) {
			$this->debug ("\t Caught exception: " . $exception->getMessage ());
			$this->setState (self::Disconnected);
			$this->socket = null;
			throw $exception;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function disconnect () {
		$this->debug ("{$this}::disconnect ()");
		$this->debug ("\t Remote Host: " . $this->getUri ()->getHost ());
		$this->debug ("\t Remote Port: " . $this->getUri ()->getPort ());
		
		if ($this->isConnectionEstablished ())
			$this->newDisconnectFrame ()->send ();
		
		if (!is_null ($this->socket))
			socket_close ($this->socket);
		
		$this->setStatus (self::Disconnected);
		$this->socket = null;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param Apache_ActiveMQ_Stomp_Frame $frame
	 */
	public function sendFrame (Apache_ActiveMQ_Stomp_Frame $frame) {
		$this->debug ("{$this}::sendFrame (Apache_ActiveMQ_Stomp_Frame)");
		$this->debug ("\t Command: '{$frame->getCommand ()}'");
		
		foreach ($frame->getHeaders () as $header => $value)
			$this->debug ("\t Header: '{$header}' => '{$value}'");
		
		$this->debug ("\t Payload: '{$frame->getPayload ()}'");
		
		// Are we connected?
		if (!$this->isConnectionOpen ())
			throw new Apache_ActiveMQ_Stomp_Exception_Send ("Connection is not yet open");
		if (!$this->isConnectionEstablished () && $frame->getCommand () != Apache_ActiveMQ_Stomp_Frame::Connect)
			throw new Apache_ActiveMQ_Stomp_Exception_Send ("Connection is not yet established");
			
		// Do the actual socket send
		try {
			$frameData = $frame->toFrame () . "\n";
			socket_send ($this->socket, $frameData, strlen ($frameData), 0);
		} catch (Exception $exception) {
			$this->debug ("\t Caught exception: " . $exception->getMessage ());
			throw $exception;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @param string $payload
	 */
	public function sendRequest ($destination, $payload) {
		assert (is_string ($destination));
		assert (is_string ($payload));
		
		$this->debug ("{$this}::sendRequest (string, string)");
		$this->debug ("\t Destination: '{$destination}'");
		$this->debug ("\t Payload: '{$payload}'");
		
		$this->newMessageFrame ($destination, $payload)->send ();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @param string $payload
	 * @return string
	 */
	public function sendRequestResponse ($destination, $payload) {
		assert (is_string ($destination));
		assert (is_string ($payload));
		
		$this->debug ("{$this}::sendRequestResponse (string, string)");
		$this->debug ("\t Destination: '{$destination}'");
		$this->debug ("\t Payload: '{$payload}'");
		
		// Build and send the request frame 
		$frame = $this->newMessageFrame ($destination, $payload);
		$frame->setGeneratedCorrelationId ();
		$frame->send ();
		
		// Save the correlation id
		$correlationId = $frame->getCorrelationId ();
		
		// Build and send the response subscription frame (using the first frame's correlation id)
		$this->newCorrelatedSubscriptionFrame ($destination, $correlationId)->send ();
		
		// Read until we get a correlated message
		$frame = $this->receiveFrame ($correlationId);
		
		// Unsubscribe from the response channel
		$this->newCorrelatedUnsubscriptionFrame ($correlationId)->send ();
		
		// Return the response data
		return $frame->getPayload ();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $correlationId
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function receiveFrame ($correlationId = null) {
		$this->debug ("{$this}::receiveFrame ()");
		
		// Is there a frame with that correlation id in the correlated queue?
		if (array_key_exists ($correlationId, $this->correlatedFrames)) {
			$this->debug ("\t Matched queued frame for correlationId " . $correlationId);
			
			$frame = $this->correlatedFrames [$correlationId];
			unset ($this->correlatedFrames [$correlationId]);
			return $frame;
		}
		
		// Are we connected?
		if (!$this->isConnectionOpen ())
			throw new Apache_ActiveMQ_Stomp_Exception_Send ("Connection is not yet open");
			
		// If the socket is not readable (or empty), just return.
		if (!$this->isSocketReadable ())
			return;
			
		// Large enough buffer?
		if (!$this->isBufferLargeEnough ()) {
			$this->debug ("\t Waiting for minimum buffer size");
			$this->debug ("\t Size Needed: {$this->receiveSize}");
			return;
		}
		
		// Is there at least one null byte to read?
		if (!$this->isBufferNullPresent ()) {
			$this->debug ("\t Buffer does not contain at least one null byte.");
			return;
		}
		
		// Are all of the headers present?
		if (!$this->isBufferHeaderPresent ()) {
			$this->debug ("\t Buffer does not contain a header / payload breakpoint.");
			return;
		}
		
		// Determine the frame size
		$matches = array ();
		
		if (preg_match ('/content-length:\s*(\d+)\n/', $this->receiveBuffer, $matches)) {
			$this->receiveSize = strpos ($this->receiveBuffer, "\n\n");
			$this->receiveSize += intval ($matches [1]);
			$this->receiveSize += 2; // For the \n\n characters
		} else {
			$this->receiveSize = strpos ($this->receiveBuffer, "\0");
		}
		
		$this->debug ("\t Buffer Size: " . strlen ($this->receiveBuffer));
		$this->debug ("\t Required Frame Size: {$this->receiveSize} + 2");
		
		// Is the buffer large enough?
		if (!$this->isBufferLargeEnough ()) {
			$this->debug ("\t Buffer is not large enough for a full read.");
			return;
		}
		
		// Extract the frame data
		$frameData = substr ($this->receiveBuffer, 0, $this->receiveSize);
		$this->receiveBuffer = substr ($this->receiveBuffer, $this->receiveSize + 2);
		$this->debug ("\t Buffer Size After Frame Read: " . strlen ($this->receiveBuffer));
		
		// Build the basic frame
		$frame = $this->newFrame ();
		$frame->setFrameData ($frameData);
		
		// Is the this message id we were looking for?
		$frameCorrelationId = $frame->getCorrelationId ();
		
		if ($correlationId && $correlationId != $frameCorrelationId) {
			$this->correlatedFrames [$frameCorrelationId] = $frame;
			return $this->receiveFrame ($correlationId); // recurse, try again
		} else {
			return $frame;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $command
	 * @param string $payload
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newFrame ($command = null, $payload = null) {
		assert (is_string ($command) || is_null ($command));
		assert (is_string ($payload) || is_null ($payload));
		
		$frame = new Apache_ActiveMQ_Stomp_Frame ();
		$frame->setConnection ($this);
		
		if ($command)
			$frame->setCommand ($command);
		if ($payload)
			$frame->setPayload ($payload);
		
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newConnectFrame () {
		$frame = $this->newFrame (Apache_ActiveMQ_Stomp_Frame::Connect);
		$frame->setUser ($this->getUri ()->getUser ());
		$frame->setPassword ($this->getUri ()->getPassword ());
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newDisconnectFrame () {
		return $this->newFrame (Apache_ActiveMQ_Stomp_Frame::Disconnect);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @param string|null $payload
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newMessageFrame ($destination, $payload = null) {
		assert (is_string ($destination));
		assert (is_string ($payload) || is_null ($payload));
		
		$frame = $this->newFrame (Apache_ActiveMQ_Stomp_Frame::Send);
		$frame->setDestination ($destination);
		$frame->setResponseDestination ("{$destination}.response");
		$frame->setPayload ($payload);
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newSubscriptionFrame ($destination) {
		assert (is_string ($destination));
		
		$frame = $this->newFrame (Apache_ActiveMQ_Stomp_Frame::Subscribe);
		$frame->setDestination ($destination);
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newUnsubscriptionFrame () {
		assert (is_string ($correlationId));
		return $this->newFrame (Apache_ActiveMQ_Stomp_Frame::Unsubscribe);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @param string $correlationId
	 * @return Apache_ActiveMQ_Stomp_Frame
	 */
	public function newCorrelatedSubscriptionFrame ($destination, $correlationId) {
		assert (is_string ($destination));
		assert (is_string ($correlationId));
		
		$frame = $this->newSubscriptionFrame ("{$destination}.response");
		$frame->setId ($correlationId);
		$frame->setSelector ("JMSCorrelationID='{$correlationId}'");
		return $frame;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $correlationId
	 * @return unknown
	 */
	public function newCorrelatedUnsubscriptionFrame ($correlationId) {
		assert (is_string ($correlationId));
		
		$frame = $this->newUnsubscriptionFrame ();
		$frame->setId ($correlationId);
		return $frame;
	
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	protected function isBufferLargeEnough () {
		return $this->receiveSize <= strlen ($this->receiveBuffer);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	protected function isBufferNullPresent () {
		return strpos ($this->receiveBuffer, "\0") !== false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	protected function isBufferHeaderPresent () {
		return strpos ($this->receiveBuffer, "\n\n") !== false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	public function isConnectionOpen () {
		return $this->socket && $this->getState () >= self::Open;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	public function isConnectionEstablished () {
		return $this->socket && $this->getState () >= self::Established;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	protected function isSocketReadable () {
		$receiveSockets [] = $this->socket;
		$null = null;
		$readable = socket_select ($receiveSockets, $null, $null, 0);
		
		if ($readable !== 0)
			return false;
			
		// Try to read into the buffer
		$receivedData = socket_read ($this->socket, 8192, PHP_BINARY_READ);
		
		// False means nothing could be read
		if ($receivedData === false)
			return false;
			
		// Add to the buffer
		$this->receiveBuffer += $receivedData;
		return !empty ($this->receiveBuffer);
	}
	
	/**
	 * @return integer
	 */
	public function getState () {
		return $this->state;
	}
	
	/**
	 * @param integer $state
	 */
	public function setState ($state) {
		assert (is_integer ($state));
		$this->state = $state;
	}
	
	/**
	 * @return Apache_ActiveMQ_Stomp_Uri
	 */
	public function getUri () {
		return $this->uri;
	}
	
	/**
	 * @param Apache_ActiveMQ_Stomp_Uri|string $uri
	 */
	public function setUri ($uri) {
		assert (is_string ($uri) || $uri instanceof Apache_ActiveMQ_Stomp_Uri);
		$this->uri = new Apache_ActiveMQ_Stomp_Uri ($uri);
	}

}


