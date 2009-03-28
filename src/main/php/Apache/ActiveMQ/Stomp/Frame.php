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

class Apache_ActiveMQ_Stomp_Frame {
	/**
	 *
	 * @return string
	 */
	public function __toString () {
		return get_class ($this);
	}
	
	/**
	 * Command used to determine if a message was send asynchronously ok
	 */
	const ASYNC_OK = "ASYNC_OK";
	
	/**
	 * Enter description here...
	 *
	 */
	const CORRELATION_ID = "correlation-id";
	
	/**
	 * Enter description here...
	 *
	 */
	const CONTENT_LENGTH = "content-length";
	
	/**
	 * Enter description here...
	 *
	 */
	const TERMINATOR = "\x00\n";
	
	/**
	 * @var array
	 */
	private $headers = array ();
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $command = '';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $body = '';
	
	/**
	 *
	 */
	public function __construct ($command = "SEND", $messageBody = '') {
		assert (is_string ($command));
		assert (is_string ($messageBody) || is_null ($messageBody));
		
		$this->setCommand ($command);
		$this->setBody ($messageBody);
		$this->generateCorrelationId ();
	}
	/**
	 * @param string $prefix
	 */
	protected function generateCorrelationId () {
		$this->setHeader (self::CORRELATION_ID, md5 (strval (rand (0, PHP_INT_MAX))));
	}
	
	/**
	 * @return string
	 */
	public function getBody () {
		return $this->body;
	}
	
	/**
	 * @return string
	 */
	public function getCommand () {
		return $this->command;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer Returns 0 or greater if the header is present, -1 if missing
	 */
	public function getContentLength () {
		$header = $this->getHeader (self::CONTENT_LENGTH);
		return strlen ($header) ? intval ($header) : -1;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getCorrelationId () {
		return $this->getHeader (self::CORRELATION_ID);
	}
	
	/**
	 * Convert this message to a string data representation
	 *
	 * @return string
	 */
	public function getEncapsulatedData () {
		$messageData = $this->getCommand () . "\n";
		
		foreach ($this->headers as $key => $value) {
			$messageData .= $key . ": " . $value . "\n";
		}
		
		$messageData .= "\n";
		$messageData .= $this->getBody ();
		$messageData .= self::TERMINATOR;
		return $messageData;
	}
	
	/**
	 * @return string
	 */
	public function getHeader ($key) {
		assert (is_string ($key));
		
		if (isset ($this->headers [$key])) {
			return $this->headers [$key];
		} else {
			return '';
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @return array
	 */
	public function getHeaders () {
		return $this->headers;
	}
	
	/**
	 * @param string $body
	 */
	public function setBody ($body) {
		assert (is_string ($body) || is_null ($body));
		$this->body = $body;
	}
	
	/**
	 * @param string $command
	 */
	public function setCommand ($command) {
		assert (is_string ($command));
		$this->command = $command;
	}
	
	/**
	 * Add a single header under a named key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setHeader ($key, $value) {
		assert (is_string ($key));
		assert (is_string ($value));
		$this->headers [trim ($key)] = trim ($value);
	}

}
