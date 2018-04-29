/*
	(C) Stadel.dk, 2009-2012
	support@stadel.dk

	Laver AJAX-kald med angivne værdier og returnerer svaret
	
	Testet i: Internet Explorer, Firefox, Chrome
	
	Angives asyncFunction laves et asynkront kald og variablerne sendes til funktionen
	når kaldet er færdig. Ellers laves et synkront kald og variablerne returneres som
	et array.
*/
function StadelLoadUrl(url, asyncFunction, hideAsyncState)
{
	// Tjekker værdier
	var httpMethod = 'GET';
	var async = (asyncFunction != null && asyncFunction != 'undefined');
	if (url.indexOf('format=ajax') == -1)
	{
		if (url.indexOf('?') == -1)
		{
			url += '?format=ajax';
		}
		else
		{
			url += '&format=ajax';
		}
	}
	
	// Opretter AJAX objekt
	var xmlDoc = null;
	try
	{
		if (window.ActiveXObject)
		{
			// Internet Explorer
			try
			{
				xmlDoc = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch(e)
			{
				xmlDoc = new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
		else
		{
			// Firefox mm
			xmlDoc = new XMLHttpRequest();
		}
	}
	catch (e)
	{
		// Exception
		return false;
	}
	
	try
	{
		// Loading
		if (async)
		{
			xmlDoc.onreadystatechange = function() {
					if (xmlDoc.readyState == 4)
					{
						self.intAjaxAsyncCount--;
						if (self.intAjaxAsyncCount == 0)
						{
							// Skjuler ikon
							divAjaxAsyncState.style.display = 'none';
						}
						if (xmlDoc.status == 200)
						{
							// Parser
							var values = Array();
							var nodes = xmlDoc.responseXML.getElementsByTagName('ajax')[0].childNodes;
							for (var i = 0; i < nodes.length; i++)
							{
								if (nodes[i].nodeType == 1)
								{
									values[nodes[i].nodeName] = nodes[i].childNodes[0].nodeValue;
								}
							}
							asyncFunction(values);
						}
					}
				};
				
			if (!hideAsyncState)
			{
				intAjaxAsyncCount++;
				divAjaxAsyncState.style.display = 'block';
			}
		}
		
		// Starter indlæsning
		// GET
		xmlDoc.open('GET', url, async);
		xmlDoc.send(null);

		if (async)
		{
			// Returnerer objekt, da det er async
			return xmlDoc;
		}
		else
		{		
			if (xmlDoc.readyState == 4 && xmlDoc.status == 200)
			{
				// Parser
				var values = Array();
				var nodes = xmlDoc.responseXML.getElementsByTagName('ajax')[0].childNodes;
				for (var i = 0; i < nodes.length; i++)
				{
					if (nodes[i].nodeType == 1)
					{
						values[nodes[i].nodeName] = nodes[i].childNodes[0].nodeValue;
					}
				}
			}
			else
			{
				return false;
			}
		}
	}
	catch (e)
	{
		// Exception
		alert(e); 
		return false;
	}
	
	// Retur
	return values;
}

function StadelAjax(url, params, agroup, ado, values, asyncFunction, httpMethod, hideAsyncState)
{
	// Tjekker værdier
	if (!ado || ado == 'undefined' || ado == null || ado.length == 0) return false;
	if (!values || values == 'undefined') values = Array();
	var async = (asyncFunction != null && asyncFunction != 'undefined');
	if (httpMethod != 'GET' && httpMethod != 'POST') httpMethod = 'POST';
	
	// Yderlige værdier
	values['submit'] = 1;
	values['do'] = ado;
	
	// Tilføjer værdier til url
	var value = '';
	var tmp = '';
	for (var key in values)
	{
		value = values[key];
		value = escape(value);
		tmp = '';
		while (tmp != value)
		{
			tmp = value;
			value = value.replace('+', '%2B');
		}
		params += '&_ajax_' + agroup + '_' + key + '=' + value;
	}
	
	// Opretter AJAX objekt
	var xmlDoc = null;
	try
	{
		if (window.ActiveXObject)
		{
			// Internet Explorer
			try
			{
				xmlDoc = new ActiveXObject("Msxml2.XMLHTTP");
			}
			catch(e)
			{
				xmlDoc = new ActiveXObject("Microsoft.XMLHTTP");
			}
		}
		else
		{
			// Firefox mm
			xmlDoc = new XMLHttpRequest();
		}
	}
	catch (e)
	{
		// Exception
		return false;
	}
	
	try
	{
		// Loading
		if (async)
		{
			xmlDoc.onreadystatechange = function() {
					if (xmlDoc.readyState == 4)
					{
						self.intAjaxAsyncCount--;
						if (self.intAjaxAsyncCount == 0)
						{
							// Skjuler ikon
							divAjaxAsyncState.style.display = 'none';
						}
						if (xmlDoc.status == 200)
						{
							// Parser
							var values = Array();
							var nodes = xmlDoc.responseXML.getElementsByTagName('ajax')[0].childNodes;
							for (var i = 0; i < nodes.length; i++)
							{
								if (nodes[i].nodeType == 1)
								{
									values[nodes[i].nodeName] = nodes[i].childNodes[0].nodeValue;
								}
							}
							asyncFunction(values);
						}
					}
				};
				
			if (!hideAsyncState)
			{
				intAjaxAsyncCount++;
				divAjaxAsyncState.style.display = 'block';
			}
		}
		
		// Starter indlæsning
		if (httpMethod == 'POST')
		{
			// POST
			xmlDoc.open('POST', url, async);
			xmlDoc.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xmlDoc.send(params);
		}
		else
		{
			// GET
			xmlDoc.open('GET', url + '?' + params, async);
			xmlDoc.send(null);
		}

		if (async)
		{
			// Returnerer objekt, da det er async
			return xmlDoc;
		}
		else
		{		
			if (xmlDoc.readyState == 4 && xmlDoc.status == 200)
			{
				// Parser
				var values = Array();
				var nodes = xmlDoc.responseXML.getElementsByTagName('ajax')[0].childNodes;
				for (var i = 0; i < nodes.length; i++)
				{
					if (nodes[i].nodeType == 1)
					{
						values[nodes[i].nodeName] = nodes[i].childNodes[0].nodeValue;
					}
				}
			}
			else
			{
				return false;
			}
		}
	}
	catch (e)
	{
		// Exception
		return false;
	}
	
	// Retur
	return values;
}

function StadelAjaxEventHandler(obj, event, func)
{
	if (obj.addEventListener)
	{
		obj.addEventListener(event, func, true);
	}
	else if (obj.attachEvent)
	{
		obj.attachEvent("on" + event, func);
	}
}

if (typeof(divAjaxAsyncState) != 'object')
{
	document.write('<div id="divAjaxAsyncStatea" style="border: 1px solid #ff0000; background: #ffffff; padding: 3px; color: #ff0000; position: absolute; top: 3px; right: 3px; display: none;"><img src="/img/ajax_loading.gif" alt="Loading..."></div>');
	var divAjaxAsyncState = document.getElementById('divAjaxAsyncStatea');
	var intAjaxAsyncCount = 0;
}
