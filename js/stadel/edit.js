function StadelEditOffsetX(elm)
{
	os = elm.offsetLeft;
	if (elm.offsetParent) os += StadelEditOffsetX(elm.offsetParent);
	return os;
}

function StadelEditOffsetY(elm)
{
	os = elm.offsetTop;
	if (elm.offsetParent) os += StadelEditOffsetY(elm.offsetParent);
	return os;
}

function StadelEditInit()
{
	
}

function StadelEditEventHandler(element, event, func)
{
	if (element.addEventListener)
	{
		element.addEventListener(event, func, true);
	}
	else if (element.attachEvent)
	{
		element.attachEvent('on' + event, func);
	}
}

StadelEditEventHandler(document, 'load', StadelEditInit);
