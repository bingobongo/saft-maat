<?php

namespace Saft;


Class Html extends Permalink {

	public static $dataAssetStr = '';


	public function __construct(){
		parent::__construct();
	}


	protected function __build($lastmod){
		# $lastmod [ superfluous] used for permalink source file download
		parent::__prepare($entry);

		$title = ucfirst(App::$author) . ': ' . array_shift($entry);
		$descr = $entry[0] === ''
			? array_shift($entry)
			: "\n\t<meta content='" . array_shift($entry) . '\' name=description>';
		$entry = implode($entry);

		echo '<!doctype html>
<html dir=ltr lang=' , Maat::$lang['code'] , ' id=' , Maat::$domainID , '>
<head>
	<meta charset=utf-8>
	<title>' , $title , '</title>' , $descr , '
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<link href=' , Elf::smartAssetURI('standard.css', 'maat') , ' rel=stylesheet>

<body class=permalink data-mod=' , $lastmod , '>
	<section id=permalink>
		<article data-ext=' , Elf::getFileExt(Pilot::$path) , ' data-asset-uri=' , App::$absolute , substr(Pilot::$path, strlen(App::$potRoot . '/'), - strlen(basename(Pilot::$path))) , self::$dataAssetStr , '>
			' , $entry , '
		</article>
		<hr>
	</section>';
		$nav = new Mav();
		unset($nav);
		echo '
	<script>
		var	conf = {
			fileExt: "' , App::FILE_EXT , '",
			maxFileSize: "' , intval(Maat::MAX_FILE_SIZE) * 1024 * 1024 , '"
		},
			lang = {
			addAssets: "' ,  Maat::$lang['add_assets'] , '",
			busy1: "' ,  Maat::$lang['busy_1'] , '",
			busy2: "' ,  Maat::$lang['busy_2'] , '",
			cancel: "' ,  Maat::$lang['cancel'] , '",
			password: "' ,  Maat::$lang['password'] , '",
			publish: "' ,  Maat::$lang['publish'] , '",
			replaceWith: "' ,  Maat::$lang['replace_with'] , '",
			error: {
				error: "' ,  Maat::$lang['error']['error'] , '",
				failure: "' ,  Maat::$lang['error']['failure'] , '",
				reboot: "' ,  Maat::$lang['error']['reboot'] , '",
				uploadExt1: "' ,  Maat::$lang['error']['upload_ext_1'] , '",
				uploadExt2: "' ,  Maat::$lang['error']['upload_ext_2']
					, str_replace('|', ', ', str_replace(array('jp(?:e|g|eg)','txt|text'), array('jpg','txt'), substr(Maat::FILE_EXT, 0, strrpos(Maat::FILE_EXT, '|'))))
					, Maat::$lang['error']['upload_ext_3']
					, substr(Maat::FILE_EXT, strrpos(Maat::FILE_EXT, '|') + 1)
					, Maat::$lang['error']['upload_ext_4'] , '",
				uploadSize1: "' ,  Maat::$lang['error']['upload_size_1'] , '",
				uploadSize2: "' ,  Maat::$lang['error']['upload_size_2'] , strval(intval(Maat::MAX_FILE_SIZE)) , ' MiB."
			},
			confirm: {
				expired: "' ,  Maat::$lang['confirm']['expired'] , '",
				expiredUnsaved: "' ,  Maat::$lang['confirm']['expired_unsaved'] , '",
				unsaved: "' ,  Maat::$lang['confirm']['unsaved'] , '",
				removeAsset1: "' ,  Maat::$lang['confirm']['remove_asset_1'] , '",
				removeAsset2: "' ,  Maat::$lang['confirm']['remove_asset_2'] , '",
				removeEntry1: "' ,  Maat::$lang['confirm']['remove_entry_1'] , '",
				removeEnd: "' ,  Maat::$lang['confirm']['remove_end'] , '"
			}
		};
	</script>
	<script src=' , Elf::smartAssetURI('mootools-core.js', 'maat') , '></script>
	<script src=' , Elf::smartAssetURI('mootools-more.js', 'maat') , '></script>
	<script src=' , Elf::smartAssetURI('standard.js', 'maat') , '></script>
	<script src=' , Elf::smartAssetURI('auth.js', 'maat') , '></script>';
		unset($descr, $entry, $title);
	}


	# @param	array

	protected function __elseAssets(&$assets){
		self::$dataAssetStr = Copilot::toDataAssetStr($assets);
	}

}
