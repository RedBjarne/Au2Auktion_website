function Popup(url, width, height)
{
	// Laver popup
	if (width == 'undefined' || width == null) width = 600;
	if (height == 'undefined' || height == null) height = 400;
	window.open(url,'popup','width='+width+',height='+height+',top='+(screen.height/2-height/2)+',left='+(screen.width/2-width/2)+',scrollbars');
}
function MenuShow(id)
{
	// Viser menu
	div = document.getElementById('divMenu'+id);
	img = document.getElementById('imgMenu'+id);
	div.style.visibility = '';
	div.style.position = '';
	img.src = '../img/icon_close.gif';
}
function MenuHide(id)
{
	// Skjuler menu
	div = document.getElementById('divMenu'+id);
	img = document.getElementById('imgMenu'+id);
	div.style.visibility = 'hidden';
	div.style.position = 'absolute';
	img.src = '../img/icon_open.gif';
}
function MenuShowHide(id)
{
	// Tjekker om en menu allerede er synlig
	if (MenuVisibleID != '' && MenuVisibleID != id)
	{
		// Skjuler menu
		MenuHide(MenuVisibleID);
	}
	// Viser eller skjuler under-menu
	div = document.getElementById('divMenu'+id);
	if (div.style.visibility == '')
	{
		// Skjuler
		MenuHide(id);
		MenuVisibleID = '';
	}
	else
	{
		// Viser
		MenuShow(id);
		MenuVisibleID = id;
	}
}
var MenuVisibleID = '';
