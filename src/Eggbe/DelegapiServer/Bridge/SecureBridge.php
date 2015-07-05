<?php
namespace Eggbe\DelegapiServer\Bridge;

use \Eggbe\Helper\Hash;
use \Eggbe\ServerBridge\Bridge;

class SecureBridge extends Bridge {

	/**
	 * @param array $Request
	 * @return string
	 */
	public function dispatch(array $Request = []){

		$this->on('!hash', function ($hash) {
			throw new Exception('Undefined hash!');
		});

		$this->on('hash', function ($hash) {
			if (!Hash::validate($hash, Hash::HAST_TYPE_MD5)) {
				throw new Exception('Invalid hash format!');
			}
		});

		return parent::dispatch($Request);
	}

}

