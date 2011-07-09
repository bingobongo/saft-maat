<?php

namespace Saft;


Class Html extends Blues {

	public function __construct(){
		parent::__construct();
	}


	protected function __build(){
		$bash = 'maat:~ ' . App::$author . '$ ';

		echo '<!doctype html>
<html dir=ltr lang=' , Maat::$lang['code'] , ' id=' , Maat::$domainID , '>
<head>
	<meta charset=utf-8>
	<title>' , ucfirst(App::$author) , '</title>
	<link href=' , App::$absolute , 'favicon.ico rel=\'shortcut icon\'>
	<link href=' , App::$absolute , 'apple-touch-icon.png rel=apple-touch-icon>
	<link href=' , Elf::smartAssetURI('standard.css', 'maat') , ' rel=stylesheet>

<body class=blues>
	<section id=blues>';

		switch (Maat::$client){
			case 0:										# bad browser
				echo '
		<p>
			', $bash , ' <br>' , Maat::$lang['error']['browser'] , '
	</section>';
				break;

			case 3:										# reboot
			case 4:										# reauth
			case 5:										# faulty
				echo '
		<p>
			', $bash , ' <br>' , Maat::$lang['error']['reboot'] , ' -' , Maat::$client , '
	</section>';
				break;

			default:									# auth
				echo '
		<noscript>
			<p>
				', $bash , ' <br>' , Maat::$lang['error']['js'] , '
		</noscript>
	</section>
	<script>
		var	lang = {
			busy1: "' ,  Maat::$lang['busy_1'] , '",
			busy2: "' ,  Maat::$lang['busy_2'] , '",
			password: "' ,  Maat::$lang['password'] , '",
			error: {
				error: "' ,  Maat::$lang['error']['error'] , '",
				failure: "' ,  Maat::$lang['error']['failure'] , '",
				reboot: "' ,  Maat::$lang['error']['reboot'] , '"
			}
		};
	</script>
	<script src=' , Elf::smartAssetURI('auth.js', 'maat') , '></script>';
				break;
		}
	}

}
