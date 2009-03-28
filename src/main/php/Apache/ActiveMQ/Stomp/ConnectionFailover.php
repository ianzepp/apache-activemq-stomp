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

class Apache_ActiveMQ_Stomp_ConnectionFailover {
	/**
	 * Enter description here...
	 *
	 * @var array
	 */
	private $connectionPool = array ();
	
	/**
	 * Enter description here...
	 *
	 * @param string $brokerUri
	 */
	public function appendToConnectionPool ($brokerUri) {
		assert (is_string ($brokerUri));
		$connection = $this->newConnection ();
		$connection->setBrokerUri ($brokerUri);
		array_push ($this->connectionPool, $connection);
	}
	
	/**
	 * @return Apache_ActiveMQ_Stomp_Connection
	 */
	public function getConnection () {
		return $this->connectionPool [0];
	}
	
	/**
	 * Enter description here...
	 *
	 * @return boolean
	 */
	public function hasAvailableConnection () {
		return count ($this->connectionPool) > 0;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param Apache_ActiveMQ_Stomp_Broker $broker
	 * @return Apache_ActiveMQ_Stomp_Connection
	 */
	public function newConnection () {
		return new Apache_ActiveMQ_Stomp_Connection ();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @param string $messageData
	 * @return string The response data
	 */
	public function sendMessage ($endpoint, $messageData) {
		assert (is_string ($endpoint));
		assert (is_string ($messageData));
		
		while ($this->hasAvailableConnection ()) {
			try {
				if ($this->getConnection ()->getStatus () == Apache_ActiveMQ_Stomp_Connection::STATUS_CLOSED) {
					$this->getConnection ()->connect ();
				}
				
				// Send the request
				return $this->getConnection ()->sendMessage ($endpoint, $messageData);
			} catch (Apache_ActiveMQ_Exception $e) {
				// Print the exception
				echo $e->getMessage (), "\n";
				echo $e->getTraceAsString (), "\n";
				echo "\n";
				
				// Shift off the offending connection
				array_shift ($this->connectionPool);
			}
		}
		
		throw new Apache_ActiveMQ_Exception ("No remaining connections available for use.");
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $endpoint
	 * @param string $messageData
	 * @return string The response data
	 */
	public function sendRequestResponse ($endpoint, $messageData) {
		assert (is_string ($endpoint));
		assert (is_string ($messageData));
		
		while ($this->hasAvailableConnection ()) {
			try {
				if ($this->getConnection ()->getStatus () == Apache_ActiveMQ_Stomp_Connection::STATUS_CLOSED) {
					$this->getConnection ()->connect ();
				}
				
				// Send the request
				return $this->getConnection ()->sendRequestResponse ($endpoint, $messageData);
			} catch (Apache_ActiveMQ_Exception $e) {
				// Print the exception
				echo $e->getMessage (), "\n";
				echo $e->getTraceAsString (), "\n";
				echo "\n";
				
				// Shift off the offending connection
				array_shift ($this->connectionPool);
			}
		}
		
		throw new Apache_ActiveMQ_Exception ("No remaining connections available for use.");
	}
}




