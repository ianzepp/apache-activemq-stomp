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

class Apache_ActiveMQ_Stomp_Uri {
	private $protocol;
	private $host;
	private $port;
	private $user;
	private $password;
	
	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 */
	public function __construct ($uri = null) {
		if (is_string ($uri)) {
			$this->setUri ($uri);
		} else if ($uri instanceof self) {
			$this->setProtocol ($uri->getProtocol ());
			$this->setHost ($uri->getHost ());
			$this->setPort ($uri->getPort ());
			$this->setUser ($uri->getUser ());
			$this->setPassword ($uri->getPassword ());
		}
	}
	
	/**
	 * @return string
	 */
	public function getHost () {
		return $this->host;
	}
	
	/**
	 * @param string $host
	 */
	public function setHost ($host) {
		$this->host = strval ($host);
	}
	
	/**
	 * @return string
	 */
	public function getPassword () {
		return $this->password;
	}
	
	/**
	 * @param string $password
	 */
	public function setPassword ($password) {
		$this->password = strval ($password);
	}
	
	/**
	 * @return string
	 */
	public function getPort () {
		return $this->port;
	}
	
	/**
	 * @param string $port
	 */
	public function setPort ($port) {
		$this->port = strval ($port);
	}
	
	/**
	 * @return string
	 */
	public function getProtocol () {
		return $this->protocol;
	}
	
	/**
	 * @param string $protocol
	 */
	public function setProtocol ($protocol) {
		$this->protocol = strval ($protocol);
	}
	
	/**
	 * @return string
	 */
	public function getUser () {
		return $this->user;
	}
	
	/**
	 * @param string $user
	 */
	public function setUser ($user) {
		$this->user = strval ($user);
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
		$this->setUser (parse_url ($uri, PHP_URL_USER));
		$this->setPassword (parse_url ($uri, PHP_URL_PASS));
		
		if ($this->getProtocol () == "stomp") {
			$this->setProtocol ("tcp");
		}
		
		if ($this->getHost () == false) {
			throw new Apache_ActiveMQ_Exception ("Unable to parse host in uri: " . $uri);
		}
		if ($this->getPort () == false) {
			$this->setPort ("61613");
		}
	}

}

