<?php

namespace Saft;

require_once('app/saft/nav.php');


Class Mav extends Nav {


	# @param	string
	# @param	string
	# @param	string
	# @param	string

	public function __construct(){
		if (Pilot::$protocol !== 'html')
			return null;

		$this->__buildNav();
	}


	protected function __buildNav(){
		$class = Pilot::$pageType;
		$this->__getPrevNextURI($prev, $next);

		$prev = $prev !== 0
			? ' data-prev=' . $prev
			: '';
		$next = $next !== 0
			? ' data-next=' . $next
			: '';

		$str = (	$class !== 'index'
				or	(	$class === 'index'
					&&	(	(	App::POT_FILTER === 1
							&&	Pilot::$contentPot !== 0
							)
						or	Pilot::$page > 1
						or	Pilot::$month !== 0
						or	Pilot::$year !== 0
						)
					)
				)
			? '<span> </span><a tabIndex=-1 id=home' . ' href=' . App::$absolute . 'maat/' . App::$author . '/ rel=index>' . Maat::$lang['index'] . '&nbsp;(<span>i</span>)</a>'
			: '';

		if ($class === 'index')
			echo '
	<nav' , $prev , $next , '>
		<a tabIndex=-1 href=javascript:void(app.exit())><strong>' , Maat::$lang['log_out'] , '</strong>&nbsp;(<span>q</span>)</a>' , $str , '<span> </span><a tabIndex=-1 href=javascript:void(entry.toggle())>' , Maat::$lang['new_entry'] , '&nbsp;(<span>n</span>)</a>
	</nav>';

		else if ($class === 'permalink')
			echo '
	<nav' , $prev , $next , '>
		<a tabIndex=-1 href=javascript:void(app.exit())><strong>' , Maat::$lang['log_out'] , '</strong>&nbsp;(<span>q</span>)</a>' , $str , '<span> </span><a tabIndex=-1 href=javascript:void(entry.toggle())>' , Maat::$lang['edit'] , '&nbsp;(<span>n</span>)</a>
	</nav>';

		if (	App::PAGINATE === 1
			&&	$class === 'index'
		)
			$this->__paginate();
	}

}
