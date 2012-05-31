
function Connection() {
	var ua = navigator.userAgent.toLowerCase();
	
	if (!window.ActiveXObject)
		this.request = new XMLHttpRequest();
	else if (ua.indexOf('msie 5') == -1)
		this.request = new ActiveXObject("Msxml2.XMLHTTP");
	else
		this.request = new ActiveXObject("Microsoft.XMLHTTP");
}

	Connection.prototype.on_task_finished = function () {
	}
	
	Connection.prototype.set_state_change = function () {
		var req = this;

		this.request.onreadystatechange = function () {
			if (req.request.readyState != 4)  // not yet finished
				return;

			// finished. Continue as before
			if (req.request.status == 200)
				req.on_task_finished();	
		}
	}
	
	Connection.prototype.get_response = function () {
		return this.request.responseText;
	}
	
	Connection.prototype.get_response_xml = function () {
		return this.request.responseXML;
	}
	
	Connection.prototype.abort = function () {
		if (this.request)
			this.request.abort();
	}

	Connection.prototype.post = function (form, action) {
		var params = '';
		var f = document.forms[form];

		for (e = 0; e < f.elements.length; e++) {
			var element = f.elements[e];
			var ename = element.getAttribute('name');
			
			if (!ename)
				continue;
			
			if (params != '')
				params += '&';
			
			
			params += ename + '=' + encodeURIComponent(element.value);
		}

		if (!action)
			action = f.getAttribute('action');
		
		this.request.abort();
		this.set_state_change();
		this.request.open("POST", action);
		this.request.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		this.request.send(params);
	}
	
	Connection.prototype.get = function (url) {
		//this.request.setRequestHeader("X-Foo-Header","Bar");
		this.request.abort();
		this.set_state_change();
		this.request.open("GET", url);
		this.request.send("");
	}
