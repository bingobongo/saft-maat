<?php

namespace Saft;


Class Blues {

	public function __construct(){
		$this->__blues();
	}


	protected function __blues(){
		# $cachename superfluous for blues; therefore, NOT standard header!
		Pilot::getContentType($contentType, $cachename);
		Elf::sendExitHeader(200, $contentType);
		$this->__build();
		unset($cachename, $contentType);

		if (	App::CHRONO === 1
			&&	Pilot::$protocol !== 'json'
		)
			echo "\n" . Elf::getSqueezedStr();

		exit;
	}

}
