<?php

namespace Saft;


Class Html extends Index {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(&$entries, $lastmod){	# $lastmod is superfluous for indexes
		$title = ucfirst(App::$author) . ': ' . htmlspecialchars(App::TITLE, ENT_QUOTES, 'utf-8', false);

		echo '<!doctype html>
<html dir=ltr lang=' , Maat::$lang['code'] , ' id=' , Maat::$domainID , '>
<head>
	<meta charset=utf-8>
	<title>' , $title , '</title>
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<link href=' , Elf::smartAssetURI('standard.css', 'maat') , ' rel=stylesheet>

<body class=index data-mod=' , $lastmod , '>
	<section id=index>';

		if (empty($entries) === false){
			$size = sizeof($entries) + 1;				# bit lower memory peak (gains with array size) than
			$r = 0;										#    “foreach (array_keys($entries) as $entryPath){” attempt
			$realToday = Elf::getCurrentDate();

			while (--$size){
				++$r;

				if ($r !== 1)
					next($entries);

				$entryPath = key($entries);
				$dataStr = ' data-ext=' . Elf::getFileExt($entryPath) . ' data-asset-uri=' . App::$absolute . substr($entryPath, strlen(App::$potRoot . '/'), - strlen(basename($entryPath)));
				$dataAssetStr = $this->__getAssetNums($entryPath);
				$assetSize = $dataAssetStr === ''
					? '<span class=tally> + 0</span>'
					: '<span class=tally> + ' . strval(substr_count($dataAssetStr, '|') + 1) . '</span>';

				echo '
		<article' , $dataStr , $dataAssetStr , intval(basename($entryPath)) > $realToday ? ' class=scheduled' : '' , '>
			<a href=/' ,  Elf::entryPathToURLi($entryPath, true) , '><span class=turn></span>' , Elf::avoidWidow(Elf::getEntryTitle($entryPath)) , '<span class=remove> ✖</span>' , $assetSize , '</a> 
		</article>';
			}											# ✏ ✐ ✎ ✖

			unset($assetSize, $dataAssetStr, $dataStr, $entryPath, $p);
		}

		echo '
		<hr>
	</section>';

		unset($entries, $title);

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
	}


	# @param	string
	# @return	string
	#
	#			Maat[, in contrast to Saft,] will only count in the assets that
	#				are in the same directory as the entry itself in order to
	#					- accelerate the output of the index pages and to
	#					- simplify the client-side asset URI creation; anyway
	#				it makes little sense to throw assets into multiple directories.

	private function __getAssetNums(&$entryPath){
		$assets = Pilot::getEntries(substr($entryPath, 0, strrpos($entryPath, '/')), $regexB = '{
			^'
			. str_replace(' ', '\s', preg_quote(Elf::cutFileExt(basename($entryPath))))
			. '
			\s\d+
			\.(?:' . App::FILE_EXT . ')
			$
			}ix'
		);
		$assets = array_reverse($assets);

		return Copilot::toDataAssetStr($assets);
	}

}
