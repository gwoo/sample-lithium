<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\socket;

/**
 * A socket adapter that uses PHP stream contexts.
 */
class Context extends \lithium\net\Socket {

	public $connection = null;

	protected $_timeout = null;

	public function open() {
		$this->timeout($this->_config['timeout']);
		return true;
	}

	public function close() {
		if (is_resource($this->connection)) {
			return fclose($this->connection);
		}
		return true;
	}

	public function eof() {
		return true;
	}

	public function read() {
		return null;
	}

	public function write($data) {
		return true;
	}

	public function timeout($time = null) {
		if ($time !== null) {
			$this->_timeout = $time;
		}
		return $this->_timeout;
	}

	public function encoding($encoding = null) {
		return false;
	}

	/**
	 * Send request and return response data
	 *
	 * @param string $message
	 * @param array $options
	 * @return string
	 */
	public function send($message, array $options = array()) {
		$defaults = array(
			'path' => null, 'classes' => array('response' => null),
			'context' => array(
				'ignore_errors' => true, 'timeout' => $this->_timeout
			)
		);
		$options += $defaults;

		if ($this->open() === false) {
			return false;
		}
		$url = is_object($message) ? $message->to('url') : $options['path'];
		$message = is_object($message) ? $message->to('context', $options['context']) : $message;

		if ($this->connection = fopen($url, 'r', false, stream_context_create($message))) {
			$meta = stream_get_meta_data($this->connection);
			$headers = $meta['wrapper_data'] ?: array();
			$message = isset($headers[0]) ? $headers[0] : null;
			$body = stream_get_contents($this->connection);
			$this->close();

			if (!$options['classes']['response']) {
				return $body;
			}
			return new $options['classes']['response'](compact('headers', 'body', 'message'));
		}
	}
}

?>