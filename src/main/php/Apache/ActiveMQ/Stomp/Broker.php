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

class Apache_ActiveMQ_Stomp_Broker {
	/**
	 *
	 * @return string
	 */
	public function __toString () {
		return get_class ($this);
	}
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	const DEFAULT_PORT = "61613";
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $host;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $port;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $username;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $password;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $protocol;
	
	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 */
	public function __construct ($uri = null) {
		if (is_string ($uri)) {
			$this->setUri ($uri);
		}
	}
	
	/**
	 * @return string
	 */
	public function getHost () {
		return $this->host;
	}
	
	/**
	 * @return string
	 */
	public function getPassword () {
		return $this->password;
	}
	
	/**
	 * @return string
	 */
	public function getPort () {
		return $this->port;
	}
	
	/**
	 * @return string
	 */
	public function getProtocol () {
		return $this->protocol;
	}
	
	/**
	 * @return string
	 */
	public function getUsername () {
		return $this->username;
	}
	
	public function getUri () {
		return sprintf ("%s://%s:%s", $this->getProtocol (), $this->getHost (), $this->getPort ());
	}
	
	/**
	 * @param string $host
	 */
	public function setHost ($host) {
		$this->host = strval ($host);
	}
	
	/**
	 * @param string $password
	 */
	public function setPassword ($password) {
		$this->password = strval ($password);
	}
	
	/**
	 * @param string $port
	 */
	public function setPort ($port) {
		$this->port = strval ($port);
	}
	
	/**
	 * @param string $protocol
	 */
	public function setProtocol ($protocol) {
		$this->protocol = strval ($protocol);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 */
	public function setUri ($uri) {
		$this->setProtocol (parse_url ($uri, PHP_URL_SCHEME));
		$this->setHost (parse_url ($uri, PHP_URL_HOST));
		$this->setPort (parse_url ($uri, PHP_URL_PORT));
		$this->setUsername (parse_url ($uri, PHP_URL_USER));
		$this->setPassword (parse_url ($uri, PHP_URL_PASS));
		
		if ($this->getProtocol () == "stomp") {
			$this->setProtocol ("tcp");
		}
		
		if ($this->getHost () == false) {
			throw new Apache_ActiveMQ_Exception ("Unable to parse broker host in uri: " . $uri);
		}
		if ($this->getPort () == false) {
			$this->setPort (self::DEFAULT_PORT);
		}
	}
	
	/**
	 * @param string $username
	 */
	public function setUsername ($username) {
		$this->username = strval ($username);
	}

}

