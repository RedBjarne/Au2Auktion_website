function SpawPGstadel()
{

}

SpawPGstadel.isDesignModeEnabled = function(editor, tbi)
{
  return (!window.opener && tbi.editor.getActivePage().editing_mode == "design");
}

SpawPGstadel.stadelPreviewClick = function(editor, tbi, sender)
{
	if (window.opener) return;
	SpawEngine.updateFields();
	var win = window.open('/js/spaw/popup_example.php?id=' + escape(editor.name) + '&css=' + escape(editor.stylesheet), 'previewpopup', 'resizable,width=800,height=600,left='+(screen.width/2-400)+',top='+(screen.height/2-300));
	win.focus();
}

SpawPGstadel.stadelFullScreenClick = function(editor, tbi, sender)
{
	if (window.opener) return;
	SpawEngine.updateFields();
	var win = window.open('/js/spaw/popup_wysiwyg.php?id=' + escape(editor.name) + '&css=' + escape(editor.stylesheet), 'previewpopup', 'resizable,width=800,height=600,left='+(screen.width/2-400)+',top='+(screen.height/2-300));
	win.focus();
}

SpawPGstadel.stadelElementChange = function(editor, tbi, sender)
{
	if (sender.selectedIndex > 0)
	{
		editor.insertHtmlAtSelection('{' + sender.options[sender.selectedIndex].value + '}');
	}
	sender.selectedIndex = 0;
    editor.updateToolbar();
    editor.focus();		
}
