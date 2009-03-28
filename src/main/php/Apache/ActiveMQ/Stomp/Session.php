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

class Apache_ActiveMQ_Stomp_Session {
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
	const CONNECTED_AT = 'CONNECTED_AT';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	const READ_BYTES = 'READ_BYTES';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	const READ_FRAMES = 'READ_FRAMES';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	const SEND_BYTES = 'SEND_BYTES';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	const SEND_FRAMES = 'SEND_FRAMES';
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	private $sessionId;
	
	/**
	 * Enter description here...
	 *
	 * @var array
	 */
	private $statistics = array ();
	
	/**
	 * Enter description here...
	 *
	 * @param string|null $sessionId
	 */
	public function __construct ($sessionId = null) {
		$this->resetStatistics ();
		$this->setSessionId ($sessionId);
		$this->setStatistic (self::CONNECTED_AT, is_null ($sessionId) ? 0 : time ());
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $incrementBy
	 */
	public function incrementReadBytes ($incrementBy) {
		assert (is_integer ($incrementBy));
		$this->statistics [self::READ_BYTES] += $incrementBy;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $incrementBy
	 */
	public function incrementReadFrames ($incrementBy = 1) {
		assert (is_integer ($incrementBy));
		$this->statistics [self::READ_FRAMES] += $incrementBy;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $incrementBy
	 */
	public function incrementSendBytes ($incrementBy) {
		assert (is_integer ($incrementBy));
		$this->statistics [self::SEND_BYTES] += $incrementBy;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $incrementBy
	 */
	public function incrementSendFrames ($incrementBy = 1) {
		assert (is_integer ($incrementBy));
		$this->statistics [self::SEND_FRAMES] += $incrementBy;
	}
	
	/**
	 * @return string
	 */
	public function getSessionId () {
		return $this->sessionId;
	}
	
	/**
	 *
	 * @param string $statisticType
	 * @return array
	 * @throws Apache_ActiveMQ_Exception If an invalid value for $statisticType
	 */
	public function getStatistic ($statisticType) {
		assert (is_string ($statisticType));
		
		if (isset ($this->statistics [$statisticType]) === false) {
			throw new Apache_ActiveMQ_Exception ("Invalid statisticType: " . $statisticType);
		} else {
			return $this->statistics [$statisticType];
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer
	 */
	public function getReadFrames () {
		return $this->getStatistic(self::READ_FRAMES);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer
	 */
	public function getReadBytes () {
		return $this->getStatistic(self::READ_BYTES);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer
	 */
	public function getSendFrames () {
		return $this->getStatistic(self::SEND_FRAMES);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return integer
	 */
	public function getSendBytes () {
		return $this->getStatistic(self::SEND_BYTES);
	}
	
	/**
	 * @return array
	 */
	public function getStatistics () {
		return $this->statistics;
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function resetStatistics () {
		$this->statistics = array ();
		$this->statistics [self::CONNECTED_AT] = 0;
		$this->statistics [self::READ_BYTES] = 0;
		$this->statistics [self::READ_FRAMES] = 0;
		$this->statistics [self::SEND_BYTES] = 0;
		$this->statistics [self::SEND_FRAMES] = 0;
	}
	
	/**
	 * @param string $sessionId
	 */
	public function setSessionId ($sessionId = null) {
		assert (is_string ($sessionId) || is_null ($sessionId));
		$this->sessionId = $sessionId;
	}
	
	/**
	 * @param string $statisticType
	 * @param mixed $statisticValue
	 */
	public function setStatistic ($statisticType, $statisticValue) {
		assert (is_string ($statisticType));
		
		if (isset ($this->statistics [$statisticType]) === false) {
			throw new Apache_ActiveMQ_Exception ("Invalid statisticType: " . $statisticType);
		} else {
			$this->statistics [$statisticType] = $statisticValue;
		}
	}
}

