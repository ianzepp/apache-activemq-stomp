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

class Apache_ActiveMQ_Stomp_ConnectionPool {
	private $connections = array ();
	
	public function registerUri ($uri) {
		assert (is_string ($uri));
		
		if (array_key_exists ($uri, $this->connections))
			return;
		
		$this->connections [$uri] = new Apache_ActiveMQ_Stomp_Connection ();
		$this->connections [$uri]->setUri ($uri);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Apache_ActiveMQ_Stomp_Connection
	 */
	public function getRandomConnection () {
		$count = count ($this->connections);
		return $count ? $this->connections [rand (0, $count - 1)] : null;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $destination
	 * @param string $payload
	 */
	public function sendRequest ($destination, $payload) {
		$connection = $this->getRandomConnection ();
		
		if (is_null ($connection))
			throw new Apache_ActiveMQ_Stomp_Exception_Send ("No connections available");
		
		if (!$connection->isConnectionOpen ())
			$connection->connect ();
			
		// Try to send the message
		$connection->sendRequest ($destination, $payload);
	}
}
