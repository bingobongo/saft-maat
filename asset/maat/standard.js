
Element.implement({

	// @param	object
	// @return	object

	cloneArticleData: function(article){
		return this.setProperties(article.getProperties('data-ext', 'data-asset-uri', 'data-asset'));
	},

	// @return	object

	removeArticleData: function(){
		return this.removeProperties('data-ext', 'data-asset-uri', 'data-asset');
	},

	// @param	string
	// @return	object

	getData: function(key){
		return this.get('data-' + key) || null;
	},

	// @param	string
	// @param	string
	// @return	object

	setData: function(key, value){

		if (value === undefined){
			this.erase('data-' + key);

		} else {
			this.set('data-' + key, value);
		}

		return this;
	},

	// @param	string
	// @return	object

	removeData: function(key){
		this.erase('data-' + key);
		return this;
	}
});


// @param	string
// @return	number

String.prototype.startsWith = function(str){
	return this.indexOf(str) === 0;						// “.indexOf()” is case sensitive
};


// @return	string

String.prototype.ucfirst = function(){
	return this.charAt(0).toUpperCase() + this.substr(1);
};


// @return	string	cut file extension inclusive “.” off

String.prototype.cutFileExt = function(){
	return this.substring(0, this.lastIndexOf('.'));
};


// @return	string	get file extension or at least the digits behind “.”

String.prototype.getFileExt = function(){
	return this.substring(this.lastIndexOf('.') + 1).toLowerCase();
};


// @return	string

String.prototype.toAssetNamePart = function(){
	return this.replace(/.*(\d{4})\/(\d{2})\/(\d{2})\/([\w-]+).*/i, '$1$2$3 $4');
};


// @return	string

String.prototype.toPermalinkDatePart = function(){
	return this.replace(/.*(\d{4})\/(\d{2})\/(\d{2})\/[\w-]+.*/i, '$1$2$3');
};


// @return	string

String.prototype.toPermalinkNamePart = function(){
	return this.replace(/.*\d{4}\/\d{2}\/\d{2}\/([\w-]+).*/i, '$1');
};


// @return	string or number

String.prototype.isEntry = function(){
	var pattern = new RegExp('^\\d{4}(?:0[1-9]|1[012])(?:0[1-9]|[12][0-9]|3[01])\\s[\\w-]+\\.(?:' + conf.fileExt + ')$', 'i');
	return pattern.test(this);
};


// @return	string or number

String.prototype.isAsset = function(){
	var pattern = new RegExp('^\\d{4}(?:0[1-9]|1[012])(?:0[1-9]|[12][0-9]|3[01])\\s[\\w-]+\\s\\d+\\.(?:' + conf.fileExt + ')$', 'i');
	return pattern.test(this);
};


// @return	number

String.prototype.isValidFileExt = function(){
	var pattern = new RegExp('^.+\\.(?:' + conf.fileExt + ')$', 'i');
	return pattern.test(this);
};


// @return	number

String.prototype.isValidMimeType = function(){
	var pattern = new RegExp('^(?:image\\/(?:gif|jpeg|png|webp)|text\\/plain|video\\/webm)$', 'i');
	return pattern.test(this);
};


// @return	string or number

String.prototype.isImage = function(){
	return /^.+\.(?:gif|jp(e|g|eg)|png|webp)$/i.test(this);
};

String.prototype.isText = function(){
	return /^.+\.(?:md|txt|text)$/i.test(this);
};

String.prototype.isVideo = function(){
	return /^.+\.(?:ogg|ogv|webm)$/i.test(this);
};


// @return	string

String.prototype.getFiletype = function(){

	if (this.isImage()){
		return this.getFileExt();

	} else if (this.isText()){
		return this.getFileExt();

	} else if (this.isVideo()){
		return this.getFileExt();

	} else {
		return '';
	}
};


// @param	object
// @param	string
// @return	number

var isValidFile = function(file, key){

	if (!file || key === 'length'){
		return 0;
	}

	if (!file.name.isValidFileExt() | !file.type.isValidMimeType()){
		alert(lang.error.uploadExt1 + file.name + lang.error.uploadExt2);
		return 0;

	} else if (file.size > conf.maxFileSize){
		alert(lang.error.uploadSize1 + file.name + lang.error.uploadSize2);
		return 0;

	} else {
		return 1;
	}
};


// @param	number
// @param	number
// @param	string

var flimflam = function(page, total, path){
	var	a,
		f = document.createDocumentFragment(),
		i = 1,
		pagination = document.getElementById('paginate'),
		span;

	++total;
	while (--total){

		if (i === page){
			span = document.createElement('span');
			span.innerHTML = i;							// html: &nbsp; or &#160; unicode: \U00A0
			f.appendChild(span);
			f.appendChild(document.createTextNode(' '));

		} else {
			a = document.createElement('a');
			a.innerHTML = i;
			a.href = path + i + '/';
			f.appendChild(a);
			f.appendChild(document.createTextNode(' '));
		}

		++i;
	}

	pagination.innerHTML = '';
	pagination.appendChild(f);
};


var app = {

	// @param	string

	initialize: function(pageType){
		pageType = this.pageType = pageType || document.body.className;
		this.baseURI = self.location.pathname.replace(/(.*\/maat\/[\w-]+)\/?.*/i, '$1/');

		switch(pageType){
			case 'index':
				entry.initialize(pageType);
				assets.initialize();
				addAssets.initialize();
				entries.initialize(pageType);			// init after assets!
				upload.initialize();
				progress.initialize();
				remove.initialize();
				break;
			case 'permalink':
				entry.initialize(pageType);
				download.initialize();
				assets.initialize();
				addAssets.initialize();
				upload.initialize();
				progress.initialize();
				break;
		}

		auth.initialize();
		keyboard.initialize();

		window.addEvents({
			'request': this.onRequest.bind(this),
			'request.complete': this.onComplete.bind(this)
		});

		$(document.body).store('ready', 1);
	},

	onRequest: function(){
		$(document.body).store('ready', 0);
	},

	onComplete: function(){
		$(document.body).store('ready', 1);
	},

	exit: function(){

		if ($(document.body).retrieve('ready')){
			window.fireEvent('request');
			window.fireEvent('exit');

		} else {
			alert(lang.busy2.ucfirst());
		}
	}

};


var	keyboard = {

	// @param	string

	initialize: function(pageType){
		pageType = this.pageType = pageType || document.body.className;
		this.cache = {};
		window.addEvents({
			'keydown': this.onKeydown.bind(this),
			'keyup': this.onKeyup.bind(this)
		});
	},

	onKeydown: function(e){
		var	el = document.activeElement,
			span;
		e = e.event.keyCode;

		if (this.cache.key){
			return null;
		}

		if (this.cache.ctrl){

			if (e !== 83){								// s
				return null;
			}

		} else if (	e !== 17							// ctrl
				&&	e !== 27							// esc
				&&	(el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')
				&&	el.placeholder
		){
			return null;
		}

		if (!$(document.body).retrieve('ready')){
			return null;
		}

		switch (e){					// keycode table, http://unixpapa.com/js/key.html
			case 17:
				this.cache.ctrl = e;					// ctrl, but on a Mac + Opera it’d be “0” and “17” ’d be cmd
				break;
			case 0:
			case 16:
			case 18:
			case 91:
			case 92:
			case 93:
			case 219:
			case 220:
			case 224:
				this.cache.key = e;						// command, branded keys
				break;
			case 27:									// esc

				if ($('entry')){

					if (	el.tagName !== 'INPUT'
						&&	el.tagName !== 'TEXTAREA'
						&&	!el.placeholder
					){
						entry.toggle();
					}

				} else {
					entries.lastly =
					entries.lastlyAsset = null;
				}

				document.activeElement.blur();
				break;
			case 68:									// d

				if (el.tagName !== 'A'){
					break;
				}

				if (	el.parentNode.tagName === 'ARTICLE'
					&&	$('index')
				){
					entries.entry = el;
					window.fireEvent('entries.remove', el.getFirst('> .remove'));
				}

				if (el.parentNode.id === 'assets'){
					span = el.getFirst('> .remove');

					if (span){
						assets.asset = el;
						assets.entry = $('entry');

						if (assets.entry){
							assets.removeAsset(el);

						} else {
							window.fireEvent('assets.remove', span);
						}
					}
				}

				break;
			case 72:									// h
				this.prevNext('prev');
				break;
			case 73:									// i
				this.redirect($('home'));
				break;
			case 74:									// j

				if (this.pageType === 'index'){
					entries.shiftFocus(1);
				}

				break;
			case 75:									// k

				if (this.pageType === 'index'){
					entries.shiftFocus(-1);
				}

				break;
			case 76:									// l
				this.prevNext('next');
				break;
			case 78:									// n

				if (this.pageType === 'index' || this.pageType === 'permalink'){
					entry.toggle();
				}

				break;
			case 79:									// o
				this.redirect(document.activeElement);
				break;
			case 80:									// p

				if (!$('entry')){
					entries.storage.redirect();
				}

				break;
			case 81:									// q

				if (!$('entry')){
					app.exit();
				}

				break;
			case 83:									// s

				if (	this.cache.ctrl
					&&	$('entry')
				){
					entry.publish();
				}

				break;
			case 84:									// t

				if ($('index')){
					el = el.getFirst('> .turn') || document.body.getElement('#active > a > .turn');

					if (el){
						window.fireEvent('entries.turn', el);
						entries.shiftFocus();				// re-focus on entry in case of previous focused asset
					}
				}

				break;
		}
	},

	onKeyup: function(e){			// keycode table, http://unixpapa.com/js/key.html
		e = e.event.keyCode;

		switch (e){
			case 17:
				delete this.cache.ctrl;					// ctrl, but on a Mac + Opera it’d be “0” and “17” ’d be cmd
				break;
			case 0:
			case 16:
			case 18:
			case 91:
			case 92:
			case 93:
			case 219:
			case 220:
			case 224:
				delete this.cache.key;					// command, branded keys
				break;
		}
	},

	// @param	object

	redirect: function(el){

		if (!el || !el.href){
			return null;
		}

		el = el.href;

		if (el.search(/^(?:https?\:\/\/\S*)/i) === -1){
			el = !el.startsWith('/')
				? 'http://' + self.location.host + '/' + el
				: 'http://' + self.location.host + el;
		}

		if (this.pageType === 'index'){
			entries.storage.store();
		}

		self.location.href = el;
	},


	// @param	string
	// @return	redirect

	prevNext: function(key){
		key = document.body.getElement('> nav').getData(key);

		if (key){
			self.location.href = key;
		}
	}

};


var entry = {

	// @param	string

	initialize: function(pageType){
		var text;
		pageType = this.pageType = pageType || document.body.className;

		this.cache = {};
		this.nav = new Element('nav');
		this.section = new Element('section#entry');
		this.article = new Element('article');
		this.article.addEvent('change:relay(input[name=file])', this.onChange.bind(this));

		this.nav.set('html', '\
		<a tabIndex=-1 href=javascript:void(entry.toggle())><strong>' + lang.cancel
			+ '</strong>&nbsp;(<span>esc</span>)</a><span> </span><a tabIndex=-1 href=javascript:void(entry.publish())>'
			+ lang.publish + '&nbsp;(<span>ctrl s</span>)</a>');

		this.date = new Element('input[name=date][type=text][placeholder=' +  this.currentDate() + ']');
		this.permalink = new Element('input[name=permalink][type=text][placeholder=permalink]');

		this.text = new Element('label#text');
		this.textarea = new Element('textarea[name=text][placeholder=text]', {
			events: {
				'keydown:pause(15)': this.calc.bind(this),
				'scroll': this.calc.bind(this)
			}
		});
		this.textcopy = new Element('div');
		this.text.appendChild(this.textarea);
		this.text.appendChild(this.textcopy);

		text = lang.replaceWith;
		this.label = new Element('label#replace-with', {
			html: text.substr(0, text.lastIndexOf('(') + 1) + '<span>'
				+ text.substring(text.lastIndexOf('(') + 1, text.lastIndexOf(')')) + '</span>)\
				<input tabIndex=-1 name=file type=file>'
		});

		window.addEvents({
			'download.success': this.onDownload.bind(this),
			'add-assets': this.addAssets.bind(this),
			'remove-asset': this.removeAsset.bind(this),

			'publish-entry.error': this.publishError.bind(this),
			'publish-entry.success': this.publishSuccess.bind(this),
			'publish-entry.complete': this.publishComplete.bind(this)
		});
	},

	toggle: function(){

		if (!$(document.body).retrieve('ready')){
			alert(lang.busy2.ucfirst());
			return null;
		}

		if ($('entry')){

			if (	this.modified() === 1
				&&	!confirm(lang.confirm.unsaved)
			){
				return null;
			}

			this.dispose();
			return null;
		}

		this.build();
	},

	dispose: function(){
		window.fireEvent('entry.dispose');

		this.emptyStore();
		this.date.value = this.permalink.value = this.textarea.value = this.textcopy.innerHTML = '';
		this.cache.fileList = {};
		this.cache.fileArr = Array();

		this.cache.section.replaces(this.section);
		this.cache.nav.replaces(this.nav);

		if (this.cache.paginate){
			this.cache.paginate.inject(this.cache.nav, 'after');

			if (this.cache.active){
				window.fireEvent('entries.turn', this.cache.active.getFirst('> a .turn'));
			}

			if (this.cache.lastly){
				entries.shiftFocus();
			}

			this.cache.active = this.cache.lastly = null;
		}

		window.scrollTo(this.cache.scroll.x, this.cache.scroll.y);
		this.article.removeArticleData().empty();
	},

	build: function(){
		this.cache.fileList = {};
		this.cache.fileArr = Array();

		document.activeElement.blur();

		this.cache.section = $(this.pageType);
		this.cache.nav = document.body.getFirst('nav');
		this.cache.paginate = $('paginate');
		this.cache.scroll = {
			'x': window.pageXOffset,
			'y': window.pageYOffset
		};

		if (this.pageType === 'index'){
			this.blank();

		} else {
			this.full();
		}

		this.nav.replaces(this.cache.nav);
		this.section.replaces(this.cache.section);

		if (this.cache.paginate){
			this.cache.paginate.dispose();
		}

		window.scrollTo(0,0);							// otherwise, Chrome will act up on video element
		this.article.style.backgroundColor = 'transparent';
	},

	blank: function(){
		this.article.appendChild(this.date);
		this.article.appendChild(this.permalink);
		this.article.appendChild(this.text);
		this.entry = this.text;

		this.article.setData('ext', 'txt').appendChild(this.label);
		this.section.appendChild(this.article);
		this.setupStore();

		this.cache.active = $('active');
		this.cache.lastly = entries.lastly;

		window.fireEvent('entry.ready', this.article);
	},

	full: function(){
		this.date.value = self.location.pathname.toPermalinkDatePart();
		this.permalink.value = self.location.pathname.toPermalinkNamePart();
		this.article.cloneArticleData(this.cache.section.getFirst('article')).appendChild(this.date);
		this.article.appendChild(this.permalink);
		this.entry = this.text;

		if (!(entryURI = this.article.getData('asset-uri')
				+ self.location.pathname.toAssetNamePart()
				+ '.' + this.article.getData('ext')
			).isText()
		){
			if (entryURI.isImage()){
				this.entry = new Element('img[src='
					+ encodeURI(entryURI) + '][alt=\''
					+ entryURI.substr(entryURI.lastIndexOf('/') + 1) + '\']');

			} else if (entryURI.isVideo()){
				this.entry = new Element('video[src=' + encodeURI(entryURI) + '][controls=true]');
			}

			this.article.appendChild(this.entry);
			this.article.appendChild(this.label);
			this.setupStore();

			window.fireEvent('entry.ready', this.article);

		} else {
			window.fireEvent('entry.plainText', [entryURI, this.article]);
		}

		this.section.appendChild(this.article);
	},

	calc: function(){
		var text = this.textarea.value;
		text = text.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		text = text.replace(/\n|\r\n/g, '<br/>0');

		if (this.textcopy.innerHTML !== text){
			this.textcopy.innerHTML = text;
		}
	},

	// @param	string

	onDownload: function(plainText){
		this.textarea.value = plainText;
		this.calc();
		this.article.appendChild(this.text);
		this.article.appendChild(this.label);
		this.setupStore();

		window.fireEvent('entry.ready', this.article);
	},

	onChange: function(e){
		var	entry,
			file = this.addEntry(e.target),
			reader;

		if (!file){
			return null;
		}

		var filename = file.name;

		if (filename.isText()){
			reader = new FileReader();
			reader.readAsText(file, 'utf-8');
			reader.onload = this.onLoad.bind(this);
			reader.onerror = this.onError;

		} else {

			if (filename.isImage()){
				entry = new Element('img[src=' + (
					Browser.chrome
						? window.webkitURL.createObjectURL(file)
						: window.URL.createObjectURL(file)
					) + '][alt=\'\']');

			} else if (filename.isVideo()){
				entry = new Element('video[src=' + (
					Browser.chrome
						? window.webkitURL.createObjectURL(file)
						: window.URL.createObjectURL(file)
					) + '][controls=true]');
			}

			entry.onload = function(){

				if (Browser.chrome){
					window.webkitURL.revokeObjectURL(this.src);

				} else {
					window.URL.revokeObjectURL(this.src);
				}
			};

			entry.replaces(this.entry);
			this.entry = entry;
		}
	},

	onLoad: function(e){
		var	filename = this.cache.fileList['100'].name,
			name;

		if (filename.isEntry()){
			name = this.article.getElementsByTagName('input');
			name[0].value = filename.substr(0, 8);
			name[1].value = filename.cutFileExt().substr(9);
		}

		this.textarea.value = e.target.result;
		this.calc();
		this.text.replaces(this.entry);
		this.entry = this.text;
	},

	onError: function(){
		alert(lang.error.error);
	},

	// @param	object	file input
	// @return	object

	addEntry: function(target){
		var	file,
			fileList = Object.filter(target.files, isValidFile);

		if (Object.getLength(fileList) > 0){
			file = fileList[0];

			if (this.cache.fileArr.contains(file.name + file.size + file.type)){

				if (this.cache.fileArr.indexOf(file.name + file.size + file.type) === 'entry'){
					return null;
				}

				var	localAssets = this.article.getElements('#assets .local'),
					str = '@' + this.cache.fileArr.indexOf(file.name + file.size + file.type).toString()
						+ ' ' + file.name.getFileExt(),
					l = localAssets.length + 1;

				while (--l){							// remove as local asset

					if (localAssets[l - 1].childNodes[0].get('text') == str){
						assets.asset = localAssets[l - 1];
						assets.removeAsset();
						break;
					}
				}
			}

			this.cache.fileList['100'] = file;			// add as local entry
			this.cache.fileArr['100'] = file.name + file.size + file.type;
			this.article.setData('ext', file.name.getFileExt());
			return file;
		}

		return null;
	},

	// @param	object	file input

	addAssets: function(target){
		var	cache = Object.getLength(this.cache.fileList),
			fileList = Object.filter(target.files, cache > 0
				? this.isUniqueValidFile.bind(this)
				: isValidFile
			);

		if (Object.getLength(fileList) > 0){
			this.addedStr = '';
			this.assetStr = this.article.getData('asset');
			this.assetStr = this.assetStr
				? '|' + this.assetStr.replace(/\.\w+/gi, '') + '|'
				: '|';

			Object.each(fileList, this.cacheAsset, this);
			window.fireEvent('add-assets.success', this.addedStr);
		}
	},

	// @param	object

	cacheAsset: function(file){
		var	assetStr = this.assetStr,
			filename = file.name,
			id = filename.cutFileExt().substr(filename.lastIndexOf(' ') + 1);
														// try to keep asset-ID
		if (!filename.isAsset() | assetStr.indexOf('|' + id + '|') !== -1){
			id = 1;

			while (assetStr.indexOf('|' + id.toString() + '|') !== -1){
				++id;
			}

			id = id.toString();
		}

		this.assetStr+= id + '|';
		this.addedStr+= this.addedStr
			? '|' + id + '.' + filename.getFileExt()
			: id + '.' + filename.getFileExt();

		this.cache.fileList[id] = file;
		this.cache.fileArr[id] = file.name + file.size + file.type;
	},

	// @param	string

	removeAsset: function(item){
		item = item.cutFileExt();

		if (this.cache.fileList[item]){
			delete this.cache.fileList[item];
		}

		if (this.cache.fileArr[item]){
			delete this.cache.fileArr[item];
		}
	},

	publish: function(){

		if (this.modified() === 0){
			this.toggle();
			return null;
		}

		this.article.dispose();
		window.fireEvent('request');
		window.fireEvent('entry.publish', this);
	},

	publishError: function(){
		this.section.appendChild(this.article);
	},

	publishSuccess: function(){

		if (this.pageType === 'index'){
			arguments = JSON.decode(arguments[0]);
			entry.cache.section.grab(new Element(arguments.article).set('html', arguments.html), 'top');
			this.success = 1;

		} else {
			self.location.href = arguments[0];
		}
	},

	publishComplete: function(){
		window.fireEvent('request.complete');

		if (this.success){
			this.section.grab(this.article);
			delete this.success;
			this.dispose();
		}
	},

	// @return number

	modified: function(){
		var	article = this.article
			ext = article.getData('ext');

		if (	article.retrieve('date') !== article.getElement('input[name="date"]').value
			|	article.retrieve('permalink') !== article.getElement('input[name="permalink"]').value
			|	article.retrieve('ext') !== ext
			|	article.retrieve('content') !== (ext === 'txt' ? this.textarea.value : this.entry.src)
			|	article.retrieve('asset') !== article.getData('asset')
			|	article.getElements('#assets .local').length
		){
			return 1;
		}

		return 0;
	},

	setupStore: function(){
		var	article = this.article
			ext = article.getData('ext');

		article.store('date', article.getElement('input[name="date"]').value);
		article.store('permalink', article.getElement('input[name="permalink"]').value);
		article.store('ext', ext);
		article.store('content', ext === 'txt' ? this.textarea.value : this.entry.src);
		article.store('asset', article.getData('asset'));
	},

	emptyStore: function(){
		this.article.eliminate('date');
		this.article.eliminate('permalink');
		this.article.eliminate('ext');
		this.article.eliminate('content');
		this.article.eliminate('asset');
	},

	// @param	object
	// @param	string
	// @return	number

	isUniqueValidFile: function(file, key){

		if (key === 'length'){
			return 0;
		}

		if (!isValidFile(file, key) | this.cache.fileArr.contains(file.name + file.size + file.type)){
			return 0;

		} else {
			return 1;
		}
	},

	// @return	string	yyyymmdd

	currentDate: function(){
		var	date = new Date(),
			month = (date.getMonth() + 1).toString(),
			day = date.getDate().toString();

		return date.getFullYear().toString()
			+ (month.length < 2 ? '0' + month : month)
			+ (day.length < 2 ? '0' + day : day);
	}

};


var entries = {

	// @param	string

	initialize: function(pageType){
		pageType = pageType || document.body.className;
		this.entries = $$('#' + pageType + ' a');

		$(pageType).addEvents({
			'click:relay(a)': this.onClick.bind(this),
			'focus:relay(article > a)': this.onFocus.bind(this)
		});

		this.lastly =
		this.lastlyAsset = null,
		this.tab = 1;
		this.storage.restore();

		window.addEvents({
			'assets.focus': this.onFocus.bind(this),	// reset lastly, don’t focus on others’ asset wrongly

			'publish-entry.success': this.add.bind(this),
			'remove-entry.success': this.remove.bind(this)
		});
	},

	onClick: function(e){
		var	classname = e.target.className,
			tagname = e.target.tagName;

		if (!$(document.body).retrieve('ready')){
			e.preventDefault();
			alert(lang.busy2.ucfirst());

		} else if (classname && tagname === 'SPAN'){
			e.preventDefault();
			this.entry = e.target.getParent('a');		// remember to remove

			if (classname === 'turn'){					// reset lastly, don’t focus on others’ asset wrongly
				this.lastlyAsset = null;
			}

			window.fireEvent('entries.' + classname, e.target);

		} else {										// else: let event propagate to parent element
			this.storage.store();
		}
	},

	// @param	string

	add: function(){
		arguments = JSON.decode(arguments[0]);
		this.entries.push(new Element(arguments.article).set('html', arguments.html));
	},

	remove: function(){
		this.entries.erase(this.entry);
		$('index').removeChild(this.entry.parentNode);
	},

	onFocus: function(e){
		var	i,
			href,
			target;

		if (this.tab){
			target = $(e.target);
			href = target.href.replace(/%20/g, ' ');

			if (href.substring(href.lastIndexOf('/') + 1).isAsset()){
				this.lastly = this.entries.indexOf(target.getParent('article > a'));
				i = $$('#assets > a').indexOf(target);

				if (i >= 0){
					this.lastlyAsset = i;
				}

			} else {
				this.lastly = this.entries.indexOf(target);
				this.lastlyAsset = null;
			}

		} else {
			this.tab = 1;
		}
	},

	// @param	number	“-1” = prev, “1” = next, “undefined” = re-focus

	shiftFocus: function(n){
		var	current = this.lastly;


		if (this.shiftFocusAssets(n)){
			return null;
		}

		if (current < this.lastly){						// = last asset, focus on next entry
			current = this.lastly < this.entries.length
				? this.lastly
				: 0;
		}


		if (current === null){

			if (n === undefined){						// nothing to re-focus
				return null;
			}

			current = n === 1
				? 0
				: this.entries.length - 1;

		} else if (document.activeElement !== this.entries[current]){
			current = current;							// re-focus on lastly focused item

		} else if (n !== undefined){

			if (current === 0){
				current = n === 1
					? ++current
					: this.entries.length - 1;

			} else if (current === this.entries.length - 1){
				current = n === 1
					? 0
					: --current;

			} else {
				current+= n;
			}

		} else {										// nothing to (re-)focus
			return null;
		}

		this.lastly = current;
		this.lastlyAsset =
		this.tab = null;
		this.entries[current].focus();
	},

	shiftFocusAssets: function(n){						// move this function to the assets object later, maybe
		var	active,
			assets,
			current = this.lastly,
			currentAsset = this.lastlyAsset,
			div = document.getElementById('assets'),
			focused = document.activeElement,
			i;

		if (!div || !div.hasChildNodes()){
			return false;
		}

		active = document.body.getElement('#active > a');
		assets = $$('#assets > a');
		i = assets.indexOf(focused);

		if (	currentAsset !== null
			&&	focused !== assets[currentAsset]
		){												// re-focus on lastly focused item
			this.tab = null;
			assets[currentAsset].focus();
			return true;
		}

		if (n === 1){

			if (i === assets.length - 1){				// = last asset, focus on next entry
				++this.lastly;
				return false;
			}

			if (	active === this.entries[current]
				&&	focused === this.entries[current]
			){											// = current entry, focus on first asset
				this.tab = null;
				assets[0].focus();
				this.lastlyAsset = 0;
				return true;
			}

			if (focused.parentNode === div){			// = , next asset
				this.tab = null;
				assets[++i].focus();
				this.lastlyAsset = i;
				return true;
			}
		}

		if (n === -1){

			if (i === 0){								// = first asset, focus on current entry
				return false;
			}

			if (	i === -1
				&&	current === this.entries.indexOf(active) + 1
			){											// = next entry, focus on last asset
				this.tab = null;
				assets[assets.length - 1].focus();
				--this.lastly;
				this.lastlyAsset = assets.length - 1;
				return true;
			}

			if (	current === 0
				&&	this.entries.indexOf(active) === this.entries.length - 1
			){											// = first entry, focus on last asset
				this.tab = null;
				assets[assets.length - 1].focus();
				this.lastly = this.entries.length - 1;
				this.lastlyAsset = assets.length - 1;
				return true;
			}

			if (focused.parentNode === div){			// = , prev asset
				this.tab = null;
				assets[--i].focus();
				this.lastlyAsset = i;
				return true;
			}
		}

		return false;
	},

	storage: {

		store: function(){
			var active = $('active');

			if (active){
				active = Object.keyOf(entries.entries, active.getFirst('> a'));
			}

			localStorage.setItem('entries.cache', JSON.encode({
				'active': active || '',
				'href': 'http://' + self.location.host + self.location.pathname,
				'lastly': entries.lastly,
				'lastlyAsset': entries.lastlyAsset,
				'scroll': {
					'x': window.pageXOffset,
					'y': window.pageYOffset
				}
			}));
		},

		restore: function(){
			var cache = localStorage.getItem('entries.cache');

			if (cache){
				cache = JSON.decode(cache);

				if (cache.href !== 'http://' + self.location.host + self.location.pathname){
					localStorage.clear();
					return null;
				}

				if (cache.active){
					window.fireEvent('entries.turn', entries.entries[cache.active].getFirst('> .turn'));
				}

				if (	cache.lastly !== null			// no unwanted re-focus
					&&	cache.lastly >= 0				// “ >= 0” because “0” = valid index number
				){
					entries.lastly = cache.lastly;
					entries.lastlyAsset = cache.lastlyAsset;
					entries.shiftFocus();
				}

				window.scrollTo(cache.scroll.x, cache.scroll.y);
			}

			localStorage.clear();
		},

		redirect: function(){
			var cache = localStorage.getItem('entries.cache');

			if (cache){
				cache = JSON.decode(cache);

				if (cache.href){
					self.location.href = cache.href;
				}
			}
		}
	}

};


var assets = {

	initialize: function(){
		this.assets = new Element('div#assets');
		this.assets.addEvents({
			'click:relay(a)': this.onClick.bind(this),
			'focus:relay(a)': this.onFocus.bind(this)
		});
		this.fragment = document.createDocumentFragment();
		window.addEvents({
			'entry.ready': this.toggle.bind(this),
			'entry.dispose': this.toggle.bind(this),

			'entries.turn': this.toggle.bind(this),
			'entries.tally': this.toggle.bind(this),

			'add-assets.success': this.update.bind(this),
			'remove-asset.success': this.removeAsset.bind(this)
		});
	},

	onClick: function(e){
		var	classname = e.target.className,
			tagname = e.target.tagName;

		if (!$(document.body).retrieve('ready')){
			e.preventDefault().stopPropagation();
			alert(lang.busy2.ucfirst());

		} else if (classname && tagname === 'SPAN'){
			e.preventDefault().stopPropagation();
			this.asset = e.target.parentNode;			// remember to remove

			if (this.entry){
				this.removeAsset();

			} else {
				window.fireEvent('assets.' + classname, e.target);
			}
		}												// else: let event propagate to parent element
	},

	onFocus: function(e){
		window.fireEvent('assets.focus', e);
	},

	// @param	object

	toggle: function(target){
		var	active = $('active'),
			assets = $('assets'),
			entry = $('entry');

		if (assets){
			this.assets = assets.dispose().empty();
		}

		if (	entry
			&&	assets
		){
			this.entry = null;							// no more entry edit mode
			window.fireEvent('assets.dispose');

		} else if (active){
			active.erase('id');

			if (!active.contains(target)){
				this.build(target);

			} else {
				window.fireEvent('assets.dispose');
			}

		} else {
			this.build(target);
		}
	},

	// @param	object

	build: function(target){

		if (target.nodeName === 'ARTICLE'){
			this.entry = 1;								// entry edit mode
			this.article = target;
			this.assetURIpart = target.getData('asset-uri') + self.location.pathname.toAssetNamePart();

		} else {
			this.article = target.getParent('article').set('id', 'active');
			this.assetURIpart = this.article.getData('asset-uri') + target.getParent('a').href.toAssetNamePart();
		}

		var	fileExt = this.article.getData('ext'),
			data = this.article.getData('asset');
		this.data = data;

		if (data){
			data = data.split('|');
			data.forEach(this.appendAsset, this);
			this.assets.appendChild(this.fragment);
		}

		this.article.appendChild(this.assets);
		window.fireEvent('assets.ready', this.article);
	},

	// @param	string

	update: function(data){

		if (this.data){
			this.data+= '|' + data;

		} else {
			this.data = data;
		}

		this.article.set('data-asset', this.data);
		data = data.split('|');

		if (this.entry){
			data.forEach(this.appendLocal, this);

		} else {
			data.forEach(this.appendAsset, this);
		}

		this.assets.appendChild(this.fragment);

		this.updateTally(data.length);
	},

	// @param	string

	appendAsset: function(item){
		var	arr = item.split('.'),
			a = new Element('a', {						// “0” = tabbing flow of document, < than “0” = priority;
				tabIndex: 0,							// “-1” = can’t be tabbed to, but focused via JavaScript
				target: '_blank',
				href: this.assetURIpart + ' ' + item,
				html: this.entry
					? '<span>@' + arr[0] + ' <small>' + item.getFiletype() + '</small></span><span class=remove> ✖</span>'
					: '@' + arr[0] + ' <small>' + item.getFiletype() + '</small><span class=remove> ✖</span>'
			});

		if (	this.entry
			&&	item.isImage()
		){
			a.set('style', 'background:url(' + encodeURI(this.assetURIpart + ' ' + item) + ') no-repeat center center');
		}

		this.fragment.appendChild(a);
	},

	// @param	string

	appendLocal: function(item){
		var	arr = item.split('.'),
			a = new Element('a', {
				'class': 'local',
				href: 'javascript:void(null)',
				html: '<span>@' + arr[0] + ' <small>' + arr[1] + '</small></span><span class=remove> ✖</span>',
				style: 'background:#ebeded'
			});

		this.fragment.appendChild(a);
	},

	removeAsset: function(){
		var	data = this.data.split('|'),
			href = this.asset.href.replace(/%20/g, ' '),
			item = this.asset.className === 'local'
				? this.asset.childNodes[0].get('text').substr(1).replace(/\s/, '.')
				: href.substr(href.lastIndexOf(' ') + 1);

		data.erase(item);
		this.data = data.join('|');
		this.article.set('data-asset', this.data);;

		if (this.entry){
			window.fireEvent('remove-asset', item);
		}

		this.updateTally();
		this.assets.removeChild(this.asset);
	},

	// @param	number

	updateTally: function(x){
		var	num,
			tally;

		if (this.entry){
			return null;
		}

		x = x || -1;
		tally = this.article.getElement('.tally'),		// “.replace(/\s/g, '')” because of Firefox
		num = parseInt(tally.get('text').replace(/\s/g, ''));
														// beware of “g” modifier, Firefox 3.6;
														//    http://blog.stevenlevithan.com/archives/es3-regexes-broken
		if (!num){
			num = 0;
		}

		tally.set('text', ' + ' + (num + x).toString());
	}

};

														// in case of “multiple” without any client-side script handler,
var addAssets = {										//    the value of the name property must end with “[]”;
														//    e.g. “name=files[]”; otherwise, multiple files
														//    won’t be recognized on server-side!
	initialize: function(){
		this.label = new Element('label#add-assets', {
			html: '\
				' + lang.addAssets.substr(0, lang.addAssets.lastIndexOf('(') + 1) + '<span>'
				  + lang.addAssets.substring(lang.addAssets.lastIndexOf('(') + 1, lang.addAssets.lastIndexOf(')'))
				  + '</span>)\
				<input tabIndex=-1 name=files type=file multiple>'
		});

		this.label.addEvents({
			'change:relay(input[type=file])': this.onChange.bind(this),
			'click': this.onClick.bind(this)
		});
		window.addEvents({
			'assets.ready': this.display.bind(this),
			'assets.dispose': this.dispose.bind(this),
			'add-assets.complete': this.enable.bind(this)
		});
	},

	onChange: function(e){

		if ($('entry')){
			e.preventDefault().stopPropagation();
			window.fireEvent('add-assets', e.target);

		} else {
			this.disable();
			window.fireEvent('request');
			window.fireEvent('addAssets.change', e.target);
		}
														// reset file input to allow for file re-selection
		document.getElementsByName('files')[0].value = '';
	},

	onClick: function(e){

		if (!$(document.body).retrieve('ready')){
			e.preventDefault().stopPropagation();
			alert(lang.busy2.ucfirst());
		}
	},

	// @param	object

	display: function(article){
		article.appendChild(this.label);
	},

	dispose: function(){
		this.label.dispose();
	},

	disable: function(){
		this.label.set('class', 'disabled').getElement('input').set('disabled', 'true');
		window.fireEvent('request');
	},

	enable: function(){
		this.label.removeAttribute('class');
		this.label.getElement('input').removeAttribute('disabled');
		window.fireEvent('request.complete');
	}

};


var upload = {

	initialize: function(){
		window.addEvents({
			'addAssets.change': this.buildAssetsFormData.bind(this),
			'entry.publish': this.buildEntryFormData.bind(this)
		});
	},

	// @param	object	whole entry object

	buildEntryFormData: function(entry){
		var	assetStr = entry.article.getData('asset'),
			cache,
			fileArr = entry.cache.fileArr,
			fileList = entry.cache.fileList,
			ext = entry.article.getData('ext');

		this.fd = new FormData();
		this.formName = 'publish-entry';
		this.fd.append('from', this.formName);

		if (entry.pageType === 'permalink'){			// append old entry file URI
			cache = entry.cache.section.getFirst('article');
			this.fd.append('entry', cache.getData('asset-uri')
				+ self.location.pathname.toAssetNamePart()
				+ '.' + cache.getData('ext')
			);

		} else {
			this.fd.append('entry', '');
		}

		var	date = entry.article.getElement('input[name="date"]'),
			permalink = entry.article.getElement('input[name="permalink"]'),
			pattern = new RegExp('^\\d{4}(?:0[1-9]|1[012])(?:0[1-9]|[12][0-9]|3[01])$', 'i');

		this.fd.append('date', pattern.test(date.value) ? date.value : date.placeholder);
		this.fd.append('permalink', permalink.value ? permalink.value.standardize().toLowerCase() : String.uniqueID());

		if (assetStr){
			this.fd.append('data-asset', assetStr);
		}

		if (fileArr['100'] && fileList['100'].name.isText()){
			delete fileArr['100'];						// don’t upload text filetype entry file
		}

		if ((' .' + ext).isText()){
			this.fd.append('content', (entry.textarea.value || entry.textarea.placeholder));
		}

		Array.each(fileArr, function(item, key){
			upload.fd.append(key, this[key]);
		}, fileList);

		window.fireEvent('upload.loadstart', [entry.section, '(' + Object.getLength(fileList).toString() + ')']);
		this.upload();
	},

	// @param	object	file input

	buildAssetsFormData: function(target){
		var	fileList = Object.filter(target.files, isValidFile),
			files = Object.getLength(fileList);

		this.formName = 'add-assets';

		if (files > 0){
			this.fd = new FormData();
			this.fd.append('from', this.formName);

			var	obj = {},
				article = $(this.formName).getParent('article'),
				assetStr = article.getData('asset'),
				namePart = obj.namePart = article.getFirst('a').href.toAssetNamePart();

			obj.assetStr = assetStr = assetStr
				? '|' + assetStr.replace(/\.\w+/gi, '') + '|'
				: '|';

			this.fd.append('entry', article.getData('asset-uri') + namePart + '.' + article.getData('ext'));
			Object.each(fileList, this.appendAsset, obj);

			window.fireEvent('upload.loadstart', [article, '(' + files.toString() + ')']);
			this.upload();

		} else {
			this.onComplete();
		}
	},

	// @param	object

	appendAsset: function(file){
		var	assetStr = this.assetStr,
			filename = file.name,
			id = filename.cutFileExt().substr(filename.lastIndexOf(' ') + 1),
			namePart = this.namePart;
														// try to keep asset-ID
		if (!filename.isAsset() | !filename.startsWith(namePart) | assetStr.indexOf('|' + id + '|') !== -1){
			id = 1;

			while (assetStr.indexOf('|' + id.toString() + '|') !== -1){
				++id;
			}

			id = id.toString();
		}

		this.assetStr+= id + '|';
		upload.fd.append(id, file);
	},

	upload: function(){
		var xhr = new XMLHttpRequest();					// function “bind” (JavaScript 1.8.5), supported since
		xhr.onload = this.onLoad.bind(this);			//    Chrome 7, Firefox 4, IE 9, but not Safari 5 nor Opera 11 yet
		xhr.onerror = this.onError.bind(this);
		xhr.onabort = this.onAbort.bind(this);
		xhr.upload.onprogress = this.onProgress;

		xhr.open('POST', self.location.pathname);
		xhr.send(this.fd);
	},

	onProgress: function(e){
		var percent;

		if (e.lengthComputable){
			percent = Math.ceil(e.loaded / e.total * 100);
			window.fireEvent('upload.progress', percent);
		}
	},

	onLoad: function(e){
		var response;

		if (e.target.status < 400){
			response = e.target.responseText || e.target.responseXML;

			if (response === lang.confirm.expired){
				alert(response);
				self.location.href.reload();

			} else if (response === lang.confirm.expiredUnsaved){

				if (!confirm(response)){
					window.fireEvent(this.formName + '.error');

				} else {
					self.location.href.reload();
				}

			} else {
				window.fireEvent(this.formName + '.success', response);
			}

		} else {
			alert(lang.error.error);
			window.fireEvent(this.formName + '.error');
		}

		this.onComplete();
	},

	onError: function(){
		window.fireEvent(this.formName + '.error');
		this.onComplete();
		alert(lang.error.failure);
	},

	onAbort: function(){
		this.onComplete();
	},

	onComplete: function(){								// complete, either succeeded or failed
		this.fd = null;
		window.fireEvent('upload.complete');
		window.fireEvent(this.formName + '.complete');
	}

};


var download = {

	// @param	string

	initialize: function(href){
		var xhr = this.xhr = new XMLHttpRequest();		// function “bind” (JavaScript 1.8.5), supported since
		xhr.onload = this.onLoad.bind(this);			//    Chrome 7, Firefox 4, IE 9, but not Safari 5 nor Opera 11 yet
		xhr.onerror = this.onError.bind(this);
		xhr.onabort = this.onAbort.bind(this);
		xhr.onprogress = this.onProgress;

		window.addEvents({
			'entry.dispose': this.stop.bind(this),
			'entry.plainText': this.start.bind(this)
		});
	},
														// 0 = href 
	start: function(){									// 1 = article element where to append progress bar
		window.fireEvent('download.loadstart', [arguments[1], '(1)']);
		this.xhr.open('GET', arguments[0] + '?' + document.body.getData('mod'));
		this.xhr.send();								// “ + '?' + document.body.getData('mod')” to bypass cache
	},

	stop: function(){

		if (this.xhr.readyState < 4){
			this.xhr.abort();
			this.xhr = new XMLHttpRequest();
		}
	},

	onProgress: function(e){
		var percent;

		if (e.lengthComputable){
			percent = Math.ceil(e.loaded / e.total * 100);
			window.fireEvent('download.progress', percent);
		}
	},

	onLoad: function(e){
		var response;

		if (e.target.status < 400){
			response = e.target.responseText || e.target.responseXML;
			window.fireEvent('download.success', response);

		} else {
			alert(lang.error.error);
		}

		this.onComplete();
	},

	onError: function(){
		this.onComplete();
		alert(lang.error.failure);
	},

	onAbort: function(){
		this.onComplete();
	},

	onComplete: function(){								// complete, either succeeded or failed
		window.fireEvent('download.complete');
	}

};


var progress = {

	initialize: function(){
		this.progress = new Element('p#progress', {
			html: '<span>0</span> % <small><span></span></small>'
		});
		this.span = this.progress.getElements('span');
		window.addEvents({
			'download.loadstart': this.display.bind(this),
			'download.progress': this.update.bind(this),
			'download.complete': this.dispose.bind(this),

			'upload.loadstart': this.display.bind(this),
			'upload.progress': this.update.bind(this),
			'upload.complete': this.dispose.bind(this)
		});
	},
														// 0 = article element where to append it, 
	display: function(){								// 1 = number of files that are uploading
		this.span[1].set('text', arguments[1]);
		arguments[0].appendChild(this.progress);
	},

	// @param	number

	update: function(percent){
		this.span[0].set('text', percent);
	},

	dispose: function(){
		this.progress.dispose();
		this.span[0].set('text', '0');
		this.span[1].set('text', '');
	}

};


var remove = {

	initialize: function(){
		window.addEvents({
			'entries.remove': this.removeEntry.bind(this),
			'assets.remove': this.removeAsset.bind(this)
		});
	},

	// @param	object

	removeEntry: function(target){
		var	a = target.getParent('a'),
			article,
			ask = lang.confirm.removeEntry1 + a.text.substr(0, a.text.indexOf(' ✖')) + lang.confirm.removeEnd;

		if (confirm(ask)){
			article = a.parentNode;

			this.fd = new FormData();
			this.fd.append('entry', article.getData('asset-uri') + a.href.toAssetNamePart() + '.' + article.getData('ext'));

			this.formName = 'remove-entry';
			this.target = target;
			this.text = target.get('text');
			target.set('text', ' …');

			this.send();
		}
	},

	// @param	object

	removeAsset: function(target){
		var	a = target.getParent('a'),
			article = a.getParent('article'),
			text = article.getFirst('a').text,
			ask = lang.confirm.removeAsset1 + a.text.substr(1, a.text.indexOf(' ') - 1) + lang.confirm.removeAsset2 + text.substr(0, text.indexOf(' ✖')) + lang.confirm.removeEnd;

		if (confirm(ask)){
			this.fd = new FormData();
			this.fd.append('entry', article.getData('asset-uri') + article.getFirst('a').href.toAssetNamePart() + '.' + article.getData('ext'));
			this.fd.append('asset', a.href.substr(a.href.indexOf(self.location.host) + self.location.host.length).replace(/%20/g, ' '));

			this.formName = 'remove-asset';
			this.target = target;
			this.text = target.get('text');
			target.set('text', ' …');

			this.send();
		}
	},

	send: function(){
		var xhr = new XMLHttpRequest()

		window.fireEvent('request');
		this.fd.append('from', this.formName);

		xhr.onload = this.onLoad.bind(this);			// function “bind” (JavaScript 1.8.5), supported since
		xhr.onerror = this.onError.bind(this);			//    Chrome 7, Firefox 4, IE 9, but not Safari 5 nor Opera 11 yet
		xhr.onabort = this.onAbort.bind(this);

		xhr.open('POST', self.location.pathname);
		xhr.send(this.fd);
	},

	onLoad: function(e){
		var response;

		if (e.target.status < 400){
			response = e.target.responseText || e.target.responseXML;

			if (	response === lang.confirm.expired
				||	response === lang.confirm.expiredUnsaved
			){											// doesn’t matter in this case => reload
				alert(lang.confirm.expired);
				self.location.href.reload();

			} else {
				window.fireEvent(this.formName + '.success');
			}

		} else {
			alert(lang.error.error);
			this.target.set('text', this.text);
		}

		this.onComplete();
	},

	onError: function(){
		this.target.set('text', this.text);
		this.onComplete();
		alert(lang.error.failure);
	},

	onAbort: function(){
		this.target.set('text', this.text);
		this.onComplete();
	},

	onComplete: function(){								// complete, either succeeded or failed
		this.fd = this.formName = this.target = this.text = null;
		window.fireEvent('request.complete');
	}

};
