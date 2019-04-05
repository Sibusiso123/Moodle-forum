var nanocms = function(elt)
{
	var urlParams = (function()
	{
		var result = new Object();
		var params = window.location.search.slice(1).split('&');
	
		for (var i = 0; i < params.length; i++)
		{
			idx = params[i].indexOf('=');
		
			if (idx > 0)
			{
				result[params[i].substring(0, idx)] = params[i].substring(idx + 1);
			}
		}
	
		return result;
	})();

	// Uses first part of path as repo name.
	// Change this according to your setup.
	var tokens = window.location.pathname.split('/');
	
	var getEventSourceDiagram = function(evt)
	{
		var source = evt.srcElement || evt.target;
	
		// Redirects to foreignObject
		if (source.ownerSVGElement == null)
		{
			var fo = source.parentNode;
	
			while (fo != null && fo.nodeName != 'foreignObject')
			{
				fo = fo.parentNode;
			}
		
			if (fo != null)
			{
				source = fo;
			}
		}
	
		// Redirects to SVG element
		if (source.ownerSVGElement != null)
		{
			source = source.ownerSVGElement;
		}
	
		return source;
	};

	if (tokens.length >= 2 && urlParams['action'] == 'edit')
	{
		// Edits an image with drawio class on double click
		document.addEventListener('dblclick', function(evt)
		{
			var url = 'https://www.draw.io/?embed=1&ui=atlas&spin=1&modified=unsavedChanges&proto=json';
			var source = getEventSourceDiagram(evt);

			if ((source.nodeName == 'IMG' && source.className == 'nanocms-diagram') ||
				(source.nodeName == 'svg' && source.className.baseVal == 'nanocms-diagram'))
			{
				// Checks if the elt is inside a content editable element
				var parent = source;
		
				while (parent != null && parent.nodeType == 1 &&
					parent.getAttribute('contentEditable') != 'true')
				{
					parent = parent.parentNode;
				}
	
				if (parent != null && parent.nodeType == 1 && parent.getAttribute('contentEditable') == 'true')
				{
					if (source.drawIoWindow == null || source.drawIoWindow.closed)
					{
						var isPng = false;
					
						// Implements protocol for loading and exporting with embedded XML
						var receive = function(evt)
						{
							if (evt.data.length > 0 && evt.source == source.drawIoWindow)
							{
								var msg = JSON.parse(evt.data);

								// Received if the editor is ready
								if (msg.event == 'init')
								{
									var data = source.getAttribute('src');
									
									if (source.nodeName == 'svg')
									{
										data = decodeURIComponent(source.getAttribute('content'));
									}
									
									isPng = data.substring(0, 15) == 'data:image/png;';
								
									// Sends the data URI with embedded XML to editor
									if (isPng)
									{
										source.drawIoWindow.postMessage(JSON.stringify({action: 'load', xmlpng: data}), '*');
									}
									else
									{
										source.drawIoWindow.postMessage(JSON.stringify({action: 'load', xml: data}), '*');
									}
								}
								// Received if the user clicks save
								else if (msg.event == 'save')
								{
									// Sends a request to export the diagram as XML with embedded PNG
									source.drawIoWindow.postMessage(JSON.stringify({action: 'export',
										format: (isPng) ? 'xmlpng' : 'xmlsvg', spinKey: 'saving'}), '*');
								}
								// Received if the export request was processed
								else if (msg.event == 'export')
								{
									if (source.nodeName == 'svg')
									{
										// Workaround for assigning class after setting outerHTML
										var wrapper = document.createElement('div');
										var svg = document.createElement('svg');
										wrapper.appendChild(svg);
										svg.outerHTML = decodeURIComponent(escape(atob(msg.data.substring(msg.data.indexOf(',') + 1))));
										wrapper.firstChild.setAttribute('class', 'nanocms-diagram');
										
										// Responsive size
										var w = parseInt(wrapper.firstChild.getAttribute('width'));
										var h = parseInt(wrapper.firstChild.getAttribute('height'));
										
										wrapper.firstChild.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
										wrapper.firstChild.setAttribute('style', 'max-height:' + h + 'px;');
										wrapper.firstChild.removeAttribute('height');
									
										// Updates the inline SVG
										source.outerHTML = wrapper.innerHTML;
									}
									else
									{
										// Updates the data URI of the image
										source.setAttribute('src', msg.data);
									}
								}
		
								// Received if the user clicks exit or after export
								if (msg.event == 'exit' || msg.event == 'export')
								{
									// Closes the editor
									window.removeEventListener('message', receive);
									source.drawIoWindow.close();
									source.drawIoWindow = null;
								}
							}
						};

						// Opens the editor
						window.addEventListener('message', receive);
						source.drawIoWindow = window.open(url);
					}
					else
					{
						// Shows existing editor window
						source.drawIoWindow.focus();
					}
				}
			}
		}, true);

		var org = window.location.hostname.split('.')[0];
		var repo = tokens[1];
		var path = tokens.slice(2).join('/');
		var ref = 'gh-pages';

		var save = function(callback)
		{
			var username = urlParams['username'] || prompt('Username');
	
			if (username == null || username.length == 0)
			{
				callback(false);
				return;
			}
	
			var password = urlParams['pass'] || prompt('Password');
	
			if (password == null || password.length == 0)
			{
				callback(false);
				return;
			}
	
			var msg = prompt('Commit Message', 'Update ' + tokens[tokens.length - 1]);
	
			if (msg == null)
			{
				callback(false);
				return;
			}
				
			var url = 'https://api.github.com/repos/' + org + '/' + repo +
				'/contents/' + path + '?ref=' + encodeURIComponent(ref);

			getSha(url, username, password, function(sha)
			{
				writeFile(url, username, password, sha, msg, function(req)
				{
					var success = req.status == 200 || req.status == 201;
			
					if (!success)
					{
						var obj = JSON.parse(req.responseText);
				
						alert((obj != null) ? obj.message : 'Error');
					}
					
					callback(success);
				});
			});
		};

		var getSha = function(url, username, password, callback)
		{
			xhr('GET', url, null, username, password, function(req)
			{
				if (req.status == 200)
				{
					var obj = JSON.parse(req.responseText);
			
					callback((obj != null) ? obj.sha : null);
				}
				else
				{
					callback(null);
				}
			});
		};

		var writeFile = function(url, username, password, sha, msg, callback)
		{
			var entity =
			{
				path: path,
				message: msg,
				content: btoa(unescape(encodeURIComponent(document.documentElement.outerHTML)))
			};			

			if (sha != null)
			{
				entity.sha = sha;
			}

			xhr('PUT', url, JSON.stringify(entity), username, password, function(req)
			{
				if (req.readyState == 4)
				{
					callback(req);
				}
			});
		};

		var xhr = function xhr(verb, url, data, username, pass, callback)
		{
			var req = new XMLHttpRequest();
			req.onreadystatechange = function()
			{
				if (req.readyState == 4)
				{
					callback(req);
				}
			};

			req.open(verb, url, true);

			req.setRequestHeader('Authorization', 'Basic ' +
				btoa(username + ':' + pass));

			req.send(data);
		};
				
		var cancel = document.createElement('button');
		cancel.innerHTML = 'Cancel';
		var initial = null;
		
		cancel.addEventListener('click', function()
		{
			if (initial == document.documentElement.outerHTML ||
				confirm('You will lose all unsaved work'))
			{
				window.location.search = '?t=' + new Date().getTime();
			}
		});
		
		var button = document.createElement('button');
		button.innerHTML = 'Save';

		button.addEventListener('click', function()
		{
			elt.removeAttribute('contenteditable');
			cancel.parentNode.removeChild(cancel);
			button.parentNode.removeChild(button);
			
			save(function(success)
			{
				document.body.appendChild(cancel);
				document.body.appendChild(button);
			
				elt.setAttribute('contenteditable', 'true');
				elt.focus();
				
				initial = document.documentElement.outerHTML;
			});
		});
		
		document.body.appendChild(cancel);
		document.body.appendChild(button);
		
		elt.setAttribute('contenteditable', 'true');
		elt.focus();
		
		initial = document.documentElement.outerHTML;
	}
	else
	{
		// Edits an image with drawio class on double click
		document.addEventListener('dblclick', function(evt)
		{
			var url = 'https://www.draw.io/?client=1&chrome=0&edit=_blank';
			var source = getEventSourceDiagram(evt);
			
			if ((source.nodeName == 'IMG' && source.className == 'nanocms-diagram') ||
				(source.nodeName == 'svg' && source.className.baseVal == 'nanocms-diagram'))
			{
				if (source.drawIoWindow == null || source.drawIoWindow.closed)
				{
					// Waits for ready message
					var receive = function(evt)
					{
						if (evt.data == 'ready' && evt.source == source.drawIoWindow)
						{
							var data = (source.nodeName == 'svg') ?
								decodeURIComponent(source.getAttribute('content')) :
								source.getAttribute('src');
			
							source.drawIoWindow.postMessage(data, '*');
							window.removeEventListener('message', receive);
						}
					};
							
					window.addEventListener('message', receive);
					source.drawIoWindow = window.open(url);
				}
				else
				{
					// Shows existing editor window
					source.drawIoWindow.focus();
				}
			}
		});
	}
};
