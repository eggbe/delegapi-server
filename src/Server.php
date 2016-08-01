<?php
namespace Eggbe\DelegapiServer;

use \Eggbe\Helper\Arr;
use \Eggbe\ServerBridge\Bridge;

use \Eggbe\DelegapiServer\Abstracts\AListener;

class Server {

	/**
	 * @const srtring
	 */
	const ON_TOKEN = 'onToken';

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
		if (!is_subclass_of($Listener, AListener::class) && !is_a($Listener, \Closure::class)) {
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
		 * We have to provide the security token to be authorized.
		 * If authorization token is not found an exception will thrown immediately.
		 */
		$Bridge->on('!token', function () {
			throw new \Exception('Access token is not found!');
		});

		/**
		 * If an authorisation token is provided it have to been checked via special listener method.
		 * This method should return a not false value to continue. Otherwise an exception will be thrown immediately.
		 *
		 * In case when the listener is not assigned all keys will be accepted.
		 */
		$Bridge->on('token', function ($token) {
			if (key_exists(self::ON_TOKEN, $this->Listeners) && !$this->Listeners[self::ON_TOKEN]($token)) {
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

		/**
		 * Please, pay attention that this library doesn't support any kinds
		 * of automatic conversions to JSON or any other format.
		 */
		return $Bridge->dispatch($Input);
	}

}
