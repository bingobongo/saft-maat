
// @param	string
// @return	number

String.prototype.startsWith = function(str){
	return this.indexOf(str) === 0;						// “.indexOf()” is case sensitive
};


// @return	string

String.prototype.toUTF8 = function(){
	return utf8_encode(this);
};


// @return	string

String.prototype.toSHA1 = function(){
	return sha1(this);
};


// @param	string
// @return	string

var utf8_encode = function(str){
	// http://github.com/kvz/phpjs/blob/master/functions/xml/utf8_encode.js
	// +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: sowberry
	// +    tweaked by: Jack
	// +   bugfixed by: Onno Marsman
	// +   improved by: Yves Sucaet
	// +   bugfixed by: Onno Marsman
	// +   bugfixed by: Ulrich
	// *     example 1: str.toUTF8('Kevin van Zonneveld');
	// *     returns 1: 'Kevin van Zonneveld'

	str+= '';
	str = str.replace(/\r\n/g, "\n");

	var	l = str.length,
		utfStr = '',
		enc, c, r,
		start =
		end = 0;

	for (r = 0; r < l; ++r){
		c = str.charCodeAt(r);
		enc = null;
 
		if (c < 128){
			++end;

		} else if (c > 127 && c < 2048){
			enc = String.fromCharCode((c >> 6) | 192) + String.fromCharCode((c & 63) | 128);

		} else {
			enc = String.fromCharCode((c >> 12) | 224) + String.fromCharCode(((c >> 6) & 63) | 128) + String.fromCharCode((c & 63) | 128);
		}

		if (enc !== null){

			if (end > start){
				utfStr+= str.substring(start, end);
			}

			utfStr+= enc;
			start =
			end = ++r;
		}
	}

	if (end > start){
		utfStr+= str.substring(start, l);
	}

	return utfStr;
};


// @param	string
// @return	string

var sha1 = function(str){
	// http://github.com/kvz/phpjs/blob/master/functions/strings/sha1.js
	// +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
	// + namespaced by: Michael White (http://getsprink.com)
	// +      input by: Brett Zamir (http://brett-zamir.me)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// -    depends on: utf8_encode
	// *     example 1: sha1('Kevin van Zonneveld');
	// *     returns 1: '54916d2e62f65b3afa6e192e6a601cdbe5cb5897'

	var rotate_left = function(n, s){
		var t4 = (n << s) | (n >>> (32 - s));
		return t4;
	};

	var cvt_hex = function(val){
		var	i,
			str = '',
			v;

		for (i = 7; i >= 0; i--){
			v = (val >>> (i * 4)) & 0x0f;
			str += v.toString(16);
		}

		return str;
	};

	str = str.toUTF8();

	var	blockstart,
		i, j,
		W = [80],
		H0 = 0x67452301,
		H1 = 0xEFCDAB89,
		H2 = 0x98BADCFE,
		H3 = 0x10325476,
		H4 = 0xC3D2E1F0,
		A, B, C, D, E,
		tmp,
		str_len = str.length,
		word_array = [];

	for (i = 0; i < str_len - 3; i += 4){
		j = str.charCodeAt(i) << 24 | str.charCodeAt(i + 1) << 16 |
		str.charCodeAt(i + 2) << 8 | str.charCodeAt(i + 3);
		word_array.push(j);
	}

	switch (str_len % 4){
		case 0:
			i = 0x080000000;
			break;
		case 1:
			i = str.charCodeAt(str_len -1 ) << 24 | 0x0800000;
			break;
		case 2:
			i = str.charCodeAt(str_len -2 ) << 24 | str.charCodeAt(str_len - 1) << 16 | 0x08000;
			break;
		case 3:
			i = str.charCodeAt(str_len - 3) << 24 | str.charCodeAt(str_len - 2) << 16 | str.charCodeAt(str_len - 1) << 8 | 0x80;
			break;
	}

	word_array.push(i);

	while ((word_array.length % 16) !== 14)
		word_array.push(0);

	word_array.push(str_len >>> 29);
	word_array.push((str_len << 3) & 0x0ffffffff);

	for (blockstart = 0; blockstart < word_array.length; blockstart += 16){

		for (i = 0; i < 16; i++){
			W[i] = word_array[blockstart+i];
		}

		for (i = 16; i <= 79; i++){
			W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);
		}

		A = H0;
		B = H1;
		C = H2;
		D = H3;
		E = H4;

		for (i = 0; i <= 19; i++){
			tmp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = tmp;
		}

		for (i = 20; i <= 39; i++){
			tmp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = tmp;
		}

		for (i = 40; i <= 59; i++){
			tmp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = tmp;
		}

		for (i = 60; i <= 79; i++){
			tmp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = tmp;
		}

		H0 = (H0 + A) & 0x0ffffffff;
		H1 = (H1 + B) & 0x0ffffffff;
		H2 = (H2 + C) & 0x0ffffffff;
		H3 = (H3 + D) & 0x0ffffffff;
		H4 = (H4 + E) & 0x0ffffffff;
	}

	tmp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
	return tmp.toLowerCase();
};


var auth = {

	// @param	string

	initialize: function(pageType){
		pageType = this.pageType =  pageType || document.body.className;
		var method = pageType === 'blues'
			? 'addBash'
			: 'addExit';

		this[method]();
	},

	addBash: function(){
		this.section = document.getElementById(this.pageType);
		var	noscript = this.section.getElementsByTagName('noscript')[0];

		if (typeof(noscript) !== 'object'){
			return null;
		}

		this.section.removeChild(noscript);
		this.setup();
		this.disable();
		this.section.appendChild(this.form);
		this.deAuth('null');							// jump between authors, let it work back and forth without restart
	},

	addExit: function(){
		window.addEvent('exit', this.cacheSection.bind(this));
	},

	cacheSection: function(){
		var cache = this.cache = {};

		if (this.user === undefined){
			this.setup();
		}

		cache.section = $(this.pageType);
		cache.nav = document.body.getFirst('nav');
		cache.paginate = $('paginate');
		cache.scroll = {
			'x': window.pageXOffset,
			'y': window.pageYOffset
		};

		this.section = new Element('section#blues', {
			html: '<article><p>' + this.bashStr + '</article>'
		});

		cache.nav.dispose();
		this.section.replaces(cache.section);

		if (cache.paginate){
			cache.paginate.dispose();
		}

		this.ready = 0;
		this.section.getElementsByTagName('p')[0].appendChild(this.busy);
		this.deAuth('quit');
	},

	restoreCacheSection: function(){
		this.cache.section.replaces(this.section);
		this.cache.nav.inject(this.cache.section, 'after');

		if (this.cache.paginate){
			this.cache.paginate.inject(this.cache.nav, 'after');
		}

		if (!$('entry')){
			entries.storage.restore();
		}

		window.scrollTo(this.cache.scroll.x, this.cache.scroll.y);
		window.fireEvent('request.complete');
	},

	setup: function(){
		var	user = document.title.toLowerCase(),
			xhr = this.xhr = new XMLHttpRequest();
		user = this.user = user.indexOf(':') > 0
			? user.substr(0, user.indexOf(':'))
			: user;

		this.bashStr = 'maat:~ ' + user + '$ ';
		this.baseURI = self.location.pathname.replace(/(.*\/maat\/[\w-]+)\/?.*/i, '$1/');
		this.baseURL = 'http://' + self.location.host + this.baseURI;

		this.fd = new FormData();
		this.fd.append('auth', '');

		xhr.onload = this.onLoad.bind(this);			// function “bind” (JavaScript 1.8.5), supported since
		xhr.onerror = this.onError.bind(this);			//    Chrome 7, Firefox 4, IE 9, but not Safari 5 nor Opera 11 yet
		xhr.onabort = this.onAbort.bind(this);

		this.buildForm();
	},

	buildForm: function(){
		var form = this.form = document.createElement('form');
		form.innerHTML = '\
			' + this.bashStr + '\
			<input name=password placeholder=' + lang.password + ' type=password autofocus>\
			<input name=login type=submit value=↩>';

		this.submit = form.getElementsByTagName('input');
		this.password = this.submit[0];
		this.submit = this.submit[1];
		this.br = document.createElement('br');
		this.busy = document.createTextNode(lang.busy1.charAt(0).toUpperCase() + lang.busy1.substr(1));
		form.addEventListener('submit', this.start.bind(this), false);
	},

	disable: function(){
		this.ready = 0;
		this.password.blur();
		this.form.removeChild(this.password);
		this.form.removeChild(this.submit);
		this.form.appendChild(this.busy);
	},

	enable: function(){
		this.ready = 1;
		this.form.removeChild(this.busy);
		this.password.value = '';
		this.form.appendChild(this.password);
		this.form.appendChild(this.submit);
		this.password.focus();							// Firefox 4
		window.scrollTo(0,window.pageYOffset*2);		// Firefox 4
	},

	// @param	string

	deAuth: function(user){
		user = user || 'null';
		this.xhr.open('POST', this.baseURL, true, user, 'null');

		if ('withCredentials' in this.xhr){
			this.xhr.withCredentials = true;
		}

		this.xhr.send(this.fd);
	},

	// @param	object

	start: function(e){	
		e.preventDefault();
		e.stopPropagation();

		if (!this.ready){
			alert(lang.busy2.charAt(0).toUpperCase() + lang.busy2.substr(1));
			return null;
		}

		if (!this.user || !this.password.value){
			return null;
		}

		this.disable();
		this.xhr.open('POST', this.baseURL, true, this.user, this.password.value.toSHA1());

		if ('withCredentials' in this.xhr){
			this.xhr.withCredentials = true;
		}

		this.xhr.send(this.fd);
	},

	stop: function(){

		if (this.xhr.readyState < 4){
			this.xhr.abort();
			this.xhr = new XMLHttpRequest();
		}
	},

	// @param	object

	onLoad: function(e){
		if (e.target.status < 400){
			var response = e.target.responseText || e.target.responseXML;

			if (typeof(this.cache) === 'object'){
				alert(lang.error.error);
				this.restoreCacheSection();

			} else if (response && response.startsWith(lang.error.reboot)){
				this.form.removeChild(this.busy);
				this.form.appendChild(document.createElement('br'));
				this.form.appendChild(document.createTextNode(response.replace(/&nbsp;/g, '\u00a0')));
				this.form.appendChild(document.createElement('br'));

			} else {
				self.location.href = this.baseURL + window.location.pathname.substr(this.baseURI.length);
			}

		} else if (typeof(this.cache) === 'object'){	// logged out successfully
			localStorage.clear();
			Object.each(this.cache, function(value, key){
				delete this.key;
			});
			this.cache = '';
			this.form.replaces(this.section.childNodes[0]);
			this.ready = 1;
			this.password.focus();

		} else if (this.xhr.getResponseHeader('X-Maat-Reset')){
			this.deAuth('null');						// reset after failed login, let it work once again (Firefox 4)

		} else {
			this.enable();
		}
	},

	onError: function(){

		if (typeof(this.cache) !== 'object'){
			this.form.appendChild(document.createElement('br'));
			this.form.appendChild(document.createTextNode(lang.error.failure.replace(/&nbsp;/g, '\u00a0')));
			this.form.appendChild(document.createElement('br'));
			this.form.appendChild(document.createTextNode(this.bash));
			this.enable();

		} else {
			alert(lang.error.failure);
			this.restoreCacheSection();
		}
	},

	onAbort: function(){

		if (typeof(this.cache) !== 'object'){
			this.enable();

		} else {
			this.restoreCacheSection();
		}
	}

};


var domready = function(){
	document.removeEventListener('DomContentLoaded', domready, false);

	if (document.body.className === 'blues'){
		auth.initialize();

	} else {
		app.initialize();
	}
};


document.addEventListener('DOMContentLoaded', domready, false);
