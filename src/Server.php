<?php
namespace Eggbe\DelegapiServer;

use \Eggbe\Helper\Arr;
use \Eggbe\ServerBridge\Bridge;

use \Eggbe\DelegapiServer\Abstracts\AListener;

class Server {

	/**
	 * @const srtring
	 */
	const ON_TOKEN = 'onAuthorize';

	/**
	 * @const string
	 */
	const ON_EXECUTE = 'onExecute';

	/**
	 * @var array
	 */
	private $Listeners = [];

	/**
	 * @param string $name
	 * @param \Closure|AListener $Listener
	 * @return Server
	 * @throws \Exception
	 */
	public function listen($name, $Listener) {
		if (!is_subclass_of($Listener, AListener::class) && !is_a($Listener, \Closure::class)){
			throw new \Exception('Invalid listener for listing action "' . $name . '"!');
		}
		if (!in_array($name, [self::ON_EXECUTE, self::ON_TOKEN])) {
			throw new \Exception('Unknown listening action "' . $name . '"!');
		}

		if (array_key_exists($name, $this->Listeners)) {
			throw new \Exception('Can not reassign listener for listening action "' . $name . '"!');
		}

		$this->Listeners[$name] = $Listener;
		return $this;
	}

	/**
	 * @param array $Input
	 * @return string
	 */
	public function dispatch(array $Input) {
		$Bridge = new Bridge();

		/**
		 *    We have to provide the secret key to be authorized.
		 *  If authorization key is not found an exception will thrown immediately.
		 */
		$Bridge->on('!token', function () {
			throw new \Exception('Access key is not found!');
		});

		/**
		 * If an authorisation key is provided it have to been checked via special listener method.
		 * This method should return a not false value to continue. Otherwise an exception will be thrown immediately.
		 *
		 * In case when the listener is not assigned all keys will be accepted.
		 */
		$Bridge->on('token', function ($key) {
			if (key_exists(self::ON_TOKEN, $this->Listeners) && !$this->Listeners[self::ON_TOKEN]($key)) {
				throw new \Exception('Access denied!');
			}
		});

		/**
		 * If authorization was passed the server will try to find necessary information
		 * about the requested action here.
		 *
		 * The action name and namespace are required.
		 */
		$Bridge->on(['namespace', 'method', ':params'], function ($namespace, $method, $Params = []) {
			return array_key_exists(self::ON_EXECUTE, $this->Listeners)
				? $this->Listeners[self::ON_EXECUTE]($namespace, $method, $Params) : false;
		});

		return $Bridge->dispatch($Input);
	}

}
