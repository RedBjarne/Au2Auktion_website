/*
	Set of functions
	Developed by: Stadel.dk, 2012
	E-mail: thomas@stadel.dk
*/

// Scroll X
function StadelScrollX()
{
	if (window.pageOffsetX)
	{
		return window.pageOffsetX;
	}
	else if (window.scrollX)
	{
		return window.scrollX;
	}
	else
	{
		return document.documentElement.scrollLeft;
	}
}

// Scroll Y
function StadelScrollY()
{
	if (window.pageOffsetY)
	{
		return window.pageOffsetY;
	}
	else if (window.scrollY)
	{
		return window.scrollY;
	}
	else
	{
		return document.documentElement.scrollTop;
	}
}

// Empties a select by id
function StadelEmptySelect(id)
{
	var sel = document.getElementById(id);
	while (sel.options.length > 0) sel.remove(sel.length - 1);
}

// Adds option to a select by id
function StadelAddSelect(id, val, tit)
{
	val = val.toString();
	tit = tit.toString();
	var opt = document.createElement('option');
	tit = tit.replace(/\s/g, '\xA0');
	opt.text = tit;
	opt.value = val;
	var sel = document.getElementById(id);
	try
	{
		sel.add(opt, null);
	}
	catch(ex)
	{
		sel.add(opt);
	}
}

// Select the value of the current selected option by id
function StadelGetSelectValue(id)
{
	var sel = document.getElementById(id);
	if (sel.selectedIndex == -1) return '';
	return sel.options[sel.selectedIndex].value;
}

// Select the text of the current selected option by id
function StadelGetSelectText(id)
{
	var sel = document.getElementById(id);
	if (sel.selectedIndex == -1) return '';
	return sel.options[sel.selectedIndex].text;
}

// Select the first option with value by id
function StadelSetSelectValue(id, val)
{
	var sel = document.getElementById(id);
	var idx = -1;
	for (var i = 0; i < sel.options.length && idx == -1; i++)
	{
		if (sel.options[i].value == val) idx = i;
	}
	if (idx == -1) idx = 0;
	sel.selectedIndex = idx;
}

// Returns left offset of element according to the document top left
function StadelOffsetLeft(elm)
{
	os = elm.offsetLeft;
	if (elm.offsetParent) os += StadelOffsetLeft(elm.offsetParent);
	return os;
}

// Returns top offset of element according to the document top left
function StadelOffsetTop(elm)
{
	os = elm.offsetTop;
	if (elm.offsetParent) os += StadelOffsetTop(elm.offsetParent);
	return os;
}

// Returns clients visible width
function StadelClientWidth()
{
	if (typeof(window.innerWidth) == 'number')
	{
		// Mozilla etc.
		return window.innerWidth;
	}
	else if (document.documentElement && document.documentElement.clientWidth)
	{
		// IE 6+
		return document.documentElement.clientWidth;
	}
	else if (document.body && document.body.clientWidth)
	{
		// IE 4
		return document.body.clientWidth;
	}	
}

// Returns clients visible height
function StadelClientHeight()
{
	if (typeof(window.innerHeight) == 'number')
	{
		// Mozilla etc.
		return window.innerHeight;
	}
	else if (document.documentElement && document.documentElement.clientHeight)
	{
		// IE 6+
		return document.documentElement.clientHeight;
	}
	else if (document.body && document.body.clientHeight)
	{
		// IE 4
		return document.body.clientHeight;
	}	
}

// Add handler to element
function StadelEventHandler(obj, event, func)
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

// Get first element by id
function StadelElement(id)
{
	return document.getElementById(id);
}

// Returns width of element by id
function StadelGetWidth(id)
{
	return StadelElement(id).offsetWidth;
}

// Returns height of element by id
function StadelGetHeight(id)
{
	return StadelElement(id).offsetHeight;
}

// Sets width of element by id
function StadelSetWidth(id, w)
{
	StadelElement(id).style.width = w.toString() + 'px';
}

// Sets height of element by id
function StadelSetHeight(id, h)
{
	StadelElement(id).style.height = h.toString() + 'px';
}

// Sets left of element by id
function StadelSetLeft(id, x)
{
	StadelElement(id).style.left = x.toString() + 'px';
}

// Sets top of element by id
function StadelSetTop(id, y)
{
	StadelElement(id).style.top = y.toString() + 'px';
}

// Hides element by id
function StadelHide(id)
{
	try
	{
		document.getElementById(id).style.display = 'none';
	}
	catch(e)
	{
	}
}

// Show element by id
function StadelShow(id)
{
	try
	{
		document.getElementById(id).style.display = 'block';
	}
	catch(e)
	{
	}
}

// Sets innerhtml of element by id
function StadelSetHtml(id, html)
{
	try
	{
		document.getElementById(id).innerHTML = html;
	}
	catch(e)
	{
	}
}

// Returns innerhtml of element by id
function StadelGetHtml(id)
{
	try
	{
		return document.getElementById(id).innerHTML;
	}
	catch(e)
	{
	}
}