<?php
namespace Eggbe\DelegapiServer\Abstracts;

abstract class AListener {

	/**
	 * @return array|false
	 * @throws \Exception
	 */
	public final function __invoke(){
		if (!method_exists($this, 'listen')){
			throw new \Exception('Method "' . get_class($this) . '::listen()" is not declared!');
		}
		return call_user_func_array([$this, 'listen'], func_get_args());
	}

}
