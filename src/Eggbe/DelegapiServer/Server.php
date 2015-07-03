<?php
namespace Eggbe\DelegapiServer;

use \Eggbe\Helper\Arr;
use \Eggbe\DelegapiServer\Bridge\SecureBridge;
use \Eggbe\DelegapiServer\Abstracts\AListener;

class Server {

	/**
	 * @const srtring
	 */
	const LISTEN_SECURE = 'secure';

	/**
	 * @const string
	 */
	const LISTEN_ACTION = 'action';

	/**
	 * @const string
	 */
	const LISTEN_ATTACH = 'attache';

	/**
	 * @var array
	 */
	private $Listeners = [];

	/**
	 * @param string $name
	 * @param AListener $Listener
	 * @return Server
	 * @throws \Exception
	 */
	public function listen($name, AListener $Listener){
		if (!in_array(($name = strtolower(trim($name))), [self::LISTEN_ACTION,
			self::LISTEN_SECURE, self::LISTEN_ATTACH])) {
				throw new \Exception('Unknown listening "' . $name . '"!');
		}

		if (array_key_exists($name, $this->Listeners)){
			throw new \Exception('Can not reassign listener for listening "' . $name . '"!');
		}

		$this->Listeners[$name] = $Listener;

		return $this;
	}

	/**
	 * @param array $Input
	 * @return string
	 */
	public function dispatch(array $Input){
		$Bridge = new SecureBridge();

		/**
		 * The method of the hash validation is specific for the any application.
		 * It should be implemented as a listener.
		 * We just throw event here.
		 */
		$Bridge->on('hash', function($hash){
			return array_key_exists(self::LISTEN_SECURE, $this->Listeners)
				? $this->Listeners[self::LISTEN_SECURE]($hash) : false;
		});

		/**
		 * The session values is specific for the any application.
		 * It should be implemented as a listener too.
		 * We just throw event here.
		 */
		$Bridge->on('attachments', function($hash){
			return array_key_exists(self::LISTEN_ATTACH, $this->Listeners)
				? $this->Listeners[self::LISTEN_ATTACH]($hash) : false;
		});

		/**
		 * This is the main action. All logic here.
		 * It should be implemented as a listener too.
		 * We just propagate event here.
		 */
		$Bridge->on(['namespace', 'method', ':params'], function($namespace, $method, $Params = []){
			return array_key_exists(self::LISTEN_ACTION, $this->Listeners)
				? $this->Listeners[self::LISTEN_ACTION]($namespace, $method, $Params) : false;
		});

		return $Bridge->dispatch($Input);
	}

}
