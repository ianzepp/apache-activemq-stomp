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
 * @package Apache_ActiveMQ_Stomp
 */

class Apache_ActiveMQ_Stomp_Frame {
	/** Command Strings */
	const Acknowledge = "ACK";
	const Connect = "CONNECT";
	const Connected = "CONNECTED";
	const Disconnect = "DISCONNECT";
	const Error = "ERROR";
	const Message = "MESSAGE";
	const Receipt = "RECEIPT";
	const Send = "SEND";
	const Subscribe = "SUBSCRIBE";
	const Unsubscribe = "UNSUBSCRIBE";
	
	/** Header Strings */
	const AcknowledgedHeader = "ack";
	const ContentLengthHeader = "content-length";
	const CorrelationIdHeader = "correlation-id";
	const DestinationHeader = "destination";
	const ExpirationHeader = "expiration";
	const IdHeader = "id";
	const MessageIdHeader = "message-id";
	const PasswordHeader = "passcode";
	const PriorityHeader = "priority";
	const ReceiptCommandHeader = "receipt-id";
	const ReceiptHeader = "receipt";
	const SelectorHeader = "selector";
	const SubscriptionHeader = "subscription";
	const TransactionHeader = "transaction";
	const UserHeader = "login";
	
	/** Message Priorities */
	const Lowest = 0;
	const Low = 1;
	const Normal = 4;
	const High = 6;
	const Highest = 9;
	
	/** Private variables */
	private $connection;
	private $command = "";
	private $headers = array ();
	private $payload = "";
	
	/** Constructor */
	public function __construct (Apache_ActiveMQ_Stomp_Frame $copy) {
		$this->connection = $copy->connection;
		$this->command = $copy->command;
		$this->headers = $copy->headers;
		$this->payload = $copy->payload;
	}
	
	/**
	 * Convert the frame command, headers, and payload to a flat string for sending.
	 * 
	 * @return string 
	 */
	public function toFrame () {
		$frameData = "";
		
		// Add in the command
		$frameData .= $this->getCommand ();
		$frameData .= "\n";
		
		// Add in each header
		foreach ($this->getHeaders () as $header => $value) {
			$frameData .= $header;
			$frameData .= ": ";
			$frameData .= $value;
			$frameData .= "\n";
		}
		
		// Add in the payload content length
		if ($this->getCommand () == self::Send) {
			$frameData .= self::ContentLengthHeader;
			$frameData .= ": ";
			$frameData .= strlen ($this->getPayload ());
			$frameData .= "\n";
		}
		
		// Linebreak between the headers and the payload
		$frameData .= "\n";
		
		// Append the payload itself
		$frameData .= $this->getPayload ();
		$frameData .= "\0";
		
		// Done.
		return $frameData;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return void
	 */
	public function acknowledge () {
		if ($this->getCommand () != self::Message)
			return; // Only message types get acknowledged
		

		$frame = new self ($this->getConnection ());
		$frame->setCommand (self::Acknowledge);
		
		if ($this->getTransaction () == null)
			$frame->setMessageId ($this->getMessageId ());
		else
			$frame->setTransaction ($this->getTransaction ());
		
		$frame->send ();
	}
	
	/**
	 * Forward this frame to the connection object for sending.
	 *
	 * @return void
	 */
	public function send () {
		$this->getConnection ()->sendFrame ($this);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return bool
	 */
	public function getAcknowledged () {
		return $this->getHeader (self::AcknowledgedHeader) == "client";
	}
	
	/**
	 * Enter description here...
	 *
	 * @param bool $acknowledged
	 * @return void
	 */
	public function setAcknowledged ($acknowledged) {
		assert (is_bool ($acknowledged));
		$this->setHeader (self::AcknowledgedHeader, $acknowledged ? "client" : "auto");
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getCommand () {
		return $this->command;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $command
	 * @return void
	 */
	public function setCommand ($command) {
		assert (is_string ($command));
		
		switch ($command) {
			case self::Acknowledge :
			case self::Connect :
			case self::Connected :
			case self::Disconnect :
			case self::Error :
			case self::Message :
			case self::Receipt :
			case self::Send :
			case self::Subscribe :
			case self::Unsubscribe :
				$this->command = $command;
				return;
		}
		
		throw new Apache_ActiveMQ_Stomp_Exception_InvalidCommand ($command);
	}
	
	public function getConnection () {
		return $this->connection;
	}
	
	public function setConnection (Apache_ActiveMQ_Stomp_Connection $connection) {
		$this->connection = $connection;
	}
	
	public function getConnectionId () {
		return is_null ($this->connection) ? null : $this->connection->getId ();
	}
	
	public function getCorrelationId () {
		return $this->getHeader (self::CorrelationIdHeader);
	}
	
	public function setCorrelationId ($correlationId) {
		assert (is_string ($correlationId));
		$this->setHeader (self::CorrelationIdHeader, $correlationId);
	}
	
	public function setGeneratedCorrelationId () {
		$this->setCorrelationId ($this->generateUuid ());
	}
	
	public function getDestination () {
		return $this->getHeader (self::DestinationHeader);
	}
	
	public function setDestination ($destination) {
		assert (is_string ($destination));
		assert (preg_match ('/^\/(topic|queue)\/.+/', $destination));
		$this->setHeader (self::DestinationHeader, $destination);
	}
	
	public function getExpiration () {
		$expiration = $this->getHeader (self::ExpirationHeader);
		
		if (is_null ($expiration) || $expiration == "0")
			return PHP_INT_MAX;
		else
			return $expiration;
	}
	
	public function setExpiration ($expiration) {
		assert (is_int ($expiration) || preg_match ('/^\d+$/', $expiration));
		$this->setHeader (self::ExpirationHeader, $expiration);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $frameData
	 */
	public function setFrameData ($frameData) {
		// Reset 
		$this->command = "";
		$this->headers = array ();
		$this->payload = "";
		
		// Frame data?
		if (is_null ($frameData))
			return;
			
		// Split the headers from the payload
		$matches = array ();
		
		if (preg_match ('/^(.+?\n)\n(.*)$/s', $frameData, $matches)) {
			list (, $headers, $payload) = $matches;
			$headers = explode ("\n", trim ($headers));
			
			// Extract the command and payload
			$this->setCommand (trim (array_shift ($headers)));
			$this->setPayload ($payload);
			
			// Extract the rest of the headers
			foreach ($headers as $header) {
				list ($name, $value) = explode (":", $header, 2);
				$this->setHeader ($name, $value);
			}
		}
	}
	
	public function getHeader ($header, $default = null) {
		assert (is_string ($header));
		
		if (array_key_exists ($header, $this->headers))
			return $this->headers [$header];
		else
			return $default;
	}
	
	public function hasHeader ($header) {
		return array_key_exists ($header, $this->headers);
	}
	
	public function setHeader ($header, $value) {
		assert (is_string ($header));
		assert (is_string ($value));
		$this->headers [$header] = $value;
	}
	
	public function getHeaders () {
		return $this->headers;
	}
	
	public function getId () {
		return $this->getHeader (self::IdHeader);
	}
	
	public function setId ($id) {
		assert (is_string ($id));
		$this->setHeader (self::IdHeader, $id);
	}
	
	public function getMessageId () {
		return $this->getHeader (self::MessageIdHeader);
	}
	
	public function setMessageId ($messageId) {
		assert (is_string ($messageId));
		$this->setHeader (self::MessageIdHeader, $messageId);
	}
	
	public function setPassword ($password) {
		assert (is_string ($password));
		$this->setHeader (self::PasswordHeader, $password);
	}
	
	public function getPayload () {
		return $this->payload;
	}
	
	public function getPayloadAsXml () {
		return simplexml_load_string ($this->getPayload ());
	}
	
	public function setPayload ($payload) {
		if ($payload instanceof SimpleXMLElement)
			$this->payload = $payload->asXML ();
		else
			$this->payload = (string) $payload;
	}
	
	public function getPriority () {
		$priority = intval ($this->getHeader (self::PriorityHeader));
		
		if ($priority >= self::Highest)
			return self::Highest;
		elseif ($priority >= self::High)
			return self::High;
		elseif ($priority >= self::Normal)
			return self::Normal;
		elseif ($priority >= self::Low)
			return self::Low;
		else
			return self::Lowest;
	}
	
	public function setPriority ($priority) {
		assert (is_int ($priority));
		
		switch ($priority) {
			case self::Highest :
			case self::High :
			case self::Normal :
			case self::Low :
			case self::Lowest :
				$this->setHeader (self::PriorityHeader, strval ($priority));
				return;
		}
		
		throw new Apache_ActiveMQ_Stomp_Exception_InvalidPriority ($priority);
	}
	
	public function getReceipt () {
		if ($this->getCommand () == self::Receipt)
			return $this->getHeader (self::ReceiptCommandHeader);
		else
			return $this->getHeader (self::ReceiptHeader);
	}
	
	public function setReceiptRequired () {
		$this->setHeader (self::Receipt, $this->generateUuid ());
	}
	
	public function getSelector () {
		return $this->getHeader (self::SelectorHeader);
	}
	
	public function setSelector ($selector) {
		assert (is_string ($selector));
		$this->setHeader (self::SelectorHeader, $selector);
	}
	
	public function getSubscription () {
		return $this->getHeader (self::SubscriptionHeader);
	}
	
	public function setSubscription ($subscription) {
		assert (is_string ($subscription));
		$this->setHeader (self::SubscriptionHeader, $subscription);
	}
	
	public function getTransaction () {
		return $this->getHeader (self::TransactionHeader);
	}
	
	public function setTransaction ($transaction) {
		assert (is_string ($transaction));
		$this->setHeader (self::TransactionHeader, $transaction);
	}
	
	public function setUser ($user) {
		assert (is_string ($user));
		$this->setHeader (self::UserHeader, $user);
	}
	
	public function generateUuid () {
		$chars = md5 (uniqid (mt_rand (), true));
		$uuid = substr ($chars, 0, 8) . '-';
		$uuid .= substr ($chars, 8, 4) . '-';
		$uuid .= substr ($chars, 12, 4) . '-';
		$uuid .= substr ($chars, 16, 4) . '-';
		$uuid .= substr ($chars, 20, 12);
		return '{' . $uuid . '}';
	}
}
