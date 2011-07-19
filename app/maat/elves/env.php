<?php

namespace Saft;


Class Env_Maat extends Env {

	function __construct(){
		parent::__construct();
	}

	protected function __checkEnvironment(){
		self::$arr = array(
			'directory' => array(),
			'file' => array(),
			'non' => array()
		);
		$this->__checkPerms();
		# must come after __checkPerms
		$this->__checkPermsMaat();
		$this->__buildPermsMsg();
	}


	private function __checkPermsMaat(){
		$root = App::$root;
		$perms = App::$perms;
		$permsAsset = $perms['asset'];
		$permsAssetParts = $perms['asset_parts'];
		$permsCache = $perms['cache'];

		# app
		$this->__isSecure($root . '/app/maat');
		$arr = $this->__rglob($root . '/app/maat', '/*', '/{*.json,*.php,*.txt}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path);

		# asset
		$this->__isSecure($root . '/asset/maat', $permsAsset);
		$arr = $this->__rglob($root . '/asset/maat', '/*', '/{*.css,*.js,*.manifest,*.woff,*.xsl}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsAsset, 0755);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts, 0644);

		# cache
		Elf::makeDirOnDemand($root . '/cache/maat', $permsCache);
		$this->__isSecure($root . '/cache/maat', $permsCache);
		$arr = $this->__rglob($root . '/cache/maat', '/*', '/{*.html,*.json,*.xml}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsCache);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts, 0644);

		# log
		$arr = array(
			'/log',
			'/log/maat',
			'/log/maat/history'
		);

		foreach ($arr as $part){
			Elf::makeDirOnDemand($root . $part, $permsCache);
			$this->__isSecure($root . $part, $permsCache);
		}

		$arr = $this->__rglob($root . '/log/maat', '/*', '/{*.log,*.zip}');

		foreach ($arr['dirs'] as $path)
			$this->__isSecure($path, $permsCache);

		foreach ($arr['files'] as $path)
			$this->__isSecure($path, $permsAssetParts, 0644);
	}

}
