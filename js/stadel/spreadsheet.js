function spreadsheet_redraw(i, focusId)
{
	// Hent ark
	var spreadsheet = spreadsheet_array[i];
	if (!spreadsheet) return;
	
	// Hent data
	var data = spreadsheet_trim(document.getElementById(spreadsheet['id']).value);
	
	// Splitter i rækker
	var rows = data.split(spreadsheet['row_split']);
	var row_count = rows.length;
	
	// Gennemløber rækker og finder antal kolonner
	var col_count = spreadsheet['col_max'];
	for (r = 0; r < rows.length; r++)
	{
		var tmp_count = rows[r].split(spreadsheet['col_split']).length;
		if (tmp_count > col_count) col_count = tmp_count;
	}
	
	// Tjekker max kolonner og rækker
	if (col_count > spreadsheet['col_max'] - 1 && spreadsheet['col_max'] > 0) col_count = spreadsheet['col_max'] - 1;
	if (row_count > spreadsheet['row_max'] - 1 && spreadsheet['row_max'] > 0) row_count = spreadsheet['row_max'] - 1;
	
	// Gemmer antal rækker og kolonner
	spreadsheet_array[i]['col_count'] = col_count;
	spreadsheet_array[i]['row_count'] = row_count;
	
	// Select bokse i kolonner
	var array_select = Array();
	
	// Gennemløber rækker og laver HTML
	var html = '<table><tr><td>&nbsp;</td>';
	for (c = 0; c <= col_count; c++)
	{
		array_select[c] = -1;
		if (spreadsheet['col_titles'][c])
		{
			tmptitle = spreadsheet['col_titles'][c];
			var pos1 = tmptitle.indexOf('[');
			var pos2 = tmptitle.indexOf(']');
			if (pos1 > -1 && pos2 > -1)
			{
				// Så er det en select-boks
				var tmpselect = '<option value="">&nbsp;</option>';
				var tmparray = tmptitle.substr(pos1 + 1, pos2 - pos1 - 1).split('|');
				tmptitle = tmptitle.substr(0, pos1);
				for (s = 0; s < tmparray.length; s++)
				{
					var tmpoption = tmparray[s].split('=');
					tmpselect = tmpselect + '<option value="' + tmpoption[0] + '">' + tmpoption[1] + '</option>';
				}
				array_select[c] = tmpselect;
			}
		}
		else
		{
			tmptitle = '<i>' + (c + 1) + '</i>';
		}
		html += '<th>' + tmptitle + '</th>';
	}
	html += '</tr>';
	for (r = 0; r <= row_count; r++)
	{
		if (spreadsheet['row_titles'][r])
		{
			tmptitle = spreadsheet['row_titles'][r];
		}
		else
		{
			tmptitle = '<i>' + (r + 1) + '</i>';
		}
		html += '<tr><th>' + tmptitle + '</th>';
		
		// Gennemløber kolonner
		if (!rows[r]) rows[r] = '';
		var cols = rows[r].split(spreadsheet['col_split']);
		for (c = 0; c <= col_count; c++)
		{
			if (!cols[c]) cols[c] = '';
			cell_data = spreadsheet_trim(cols[c]);
			cell_data = spreadsheet_htmlentities(cell_data);
			
			// On change
			var on_change = 'spreadsheet_update(' + i + '); ';
			if (r == row_count && (row_count < spreadsheet['row_max'] || spreadsheet['row_max'] == 0) && 
				c == col_count && (col_count < spreadsheet['col_max'] || spreadsheet['col_max'] == 0))
			{
				var next_c = c + 1;
				var next_r = r;
				if (next_c > col_count)
				{
					next_c = 0;
					next_r++;
				}
				on_change += 'spreadsheet_redraw(' + i + ', \'spreadsheet_' + spreadsheet['id'] + '_' + next_r + '_' + next_c + '\');';
			}
			
			if (array_select[c] == -1)
			{
				// Tekst-felt
				html += '<td><input type="text" id="spreadsheet_' + spreadsheet['id'] + '_' + r + '_' + c + '" value="' + cell_data + '" onchange="' + on_change + '"></td>';
			}
			else
			{
				// Select boks
				html += '<td><select id="spreadsheet_' + spreadsheet['id'] + '_' + r + '_' + c + '" onchange="' + on_change + '">'+
					array_select[c].replace('value="' + cell_data + '"', 'value="' + cell_data + '" selected') + '</select></td>';
			}
		}
		
		if (r < row_count)
		{
			html += '<td>';
			if (r > 0)
			{
				html += ' <a href="javascript:spreadsheet_move_up(' + i + ', ' + r + ');void(0);">OP</a> ';
			}
			else
			{
				html += ' <font color=lightgrey>OP</font> ';
			}
			if (r < row_count - 1)
			{
				html += ' <a href="javascript:spreadsheet_move_down(' + i + ', ' + r + ');void(0);">NED</a> ';
			}
			else
			{
				html += ' <font color=lightgrey>NED</font> ';
			}
			html += '</td>';
		}
		html += '</tr>';
	}
	html += '</table>';
	
	document.getElementById('spreadsheet_div_' + spreadsheet['id']).innerHTML = html;
	
	if (focusId)
	{
		try
		{
			document.getElementById(focusId).focus();
		}
		catch(e)
		{
			document.getElementById('spreadsheet_' + spreadsheet['id'] + '_0_0').focus();
		}
	}
}

function spreadsheet_move_up(i, r_move)
{
	// Hent ark
	var spreadsheet = spreadsheet_array[i];
	if (!spreadsheet) return;

	// Gennemløber rækker og kolonner og laver data
	var data = '';
	var next_line = '';
	for (r = 0; r <= spreadsheet['row_count']; r++)
	{
		var line = '';
		for (c = 0; c <= spreadsheet['col_count']; c++)
		{
			if (c > 0) line += spreadsheet['col_split'];
			line += document.getElementById('spreadsheet_' + spreadsheet['id'] + '_' + r + '_' + c).value.replace(spreadsheet['row_split'], '').replace(spreadsheet['col_split'], '');
		}
		if (line.replace(spreadsheet['col_split'], '') != '')
		{
			if (r_move - 1 == r)
			{
				next_line = line;
			}
			else
			{
				if (data != '') data += spreadsheet['row_split'];
				data += line;
				if (next_line != '')
				{
					data += spreadsheet['row_split'] + next_line;
					next_line = '';
				}
			}
		}
	}
	
	if (next_line != '')
	{
		if (data != '') data += spreadsheet['row_split'];
		data += line;
	}
	
	// Gemmer data i felt
	document.getElementById(spreadsheet['id']).value = data;
	
	spreadsheet_redraw(i);
}

function spreadsheet_move_down(i, r_move)
{
	// Hent ark
	var spreadsheet = spreadsheet_array[i];
	if (!spreadsheet) return;

	// Gennemløber rækker og kolonner og laver data
	var data = '';
	var next_line = '';
	for (r = 0; r <= spreadsheet['row_count']; r++)
	{
		var line = '';
		for (c = 0; c <= spreadsheet['col_count']; c++)
		{
			if (c > 0) line += spreadsheet['col_split'];
			line += document.getElementById('spreadsheet_' + spreadsheet['id'] + '_' + r + '_' + c).value.replace(spreadsheet['row_split'], '').replace(spreadsheet['col_split'], '');
		}
		if (line.replace(spreadsheet['col_split'], '') != '')
		{
			if (r_move == r)
			{
				next_line = line;
			}
			else
			{
				if (data != '') data += spreadsheet['row_split'];
				data += line;
				if (next_line != '')
				{
					data += spreadsheet['row_split'] + next_line;
					next_line = '';
				}
			}
		}
	}
	
	if (next_line != '')
	{
		if (data != '') data += spreadsheet['row_split'];
		data += line;
	}
	
	// Gemmer data i felt
	document.getElementById(spreadsheet['id']).value = data;
	spreadsheet_redraw(i);
}

function spreadsheet(id, col_titles, row_titles, col_split, row_split)
{
	// Tjekker variabler
	col_max = 0;
	row_max = 0;
	if (!col_titles)
	{
		col_titles = Array();
	}
	else
	{
		col_titles = col_titles.split(',');
		col_max = col_titles.length;
	}
	if (!row_titles)
	{
		row_titles = Array();
	}
	else
	{
		row_titles = row_titles.split(',');
		row_max = row_titles.length;
	}
	if (!col_split) col_split = '|';
	if (!row_split) row_split = '\n';
	
	// Indsæt editor
	document.write('<div id="spreadsheet_div_' + id + '"></div>');
	
	// Tilføj til array
	var i = spreadsheet_array.length;
	spreadsheet_array[i] = Array();
	spreadsheet_array[i]['id'] = id;
	spreadsheet_array[i]['row_titles'] = row_titles;
	spreadsheet_array[i]['col_titles'] = col_titles;
	spreadsheet_array[i]['col_max'] = col_max;
	spreadsheet_array[i]['row_max'] = row_max;
	spreadsheet_array[i]['col_split'] = col_split;
	spreadsheet_array[i]['row_split'] = row_split;
	
	// Opdater editor
	spreadsheet_redraw(i);
}

function spreadsheet_trim(str)
{
	while(str.substr(0, 1) == ' ' || str.substr(0, 1) == '\r' || str.substr(0, 1) == '\n')
	{
		str = str.substr(1);
	}
	while(str.substr(str.length - 1, 1) == ' ' || str.substr(str.length - 1, 1) == '\r' || str.substr(str.length - 1, 1) == '\n')
	{
		str = str.substr(0, str.length - 1);
	}
	return str;
}

function spreadsheet_removeall(str, rem)
{
	var tmp = '';
	while (tmp != str)
	{
		tmp = str;
		str = str.replace(rem, '');
	}
	return str;
}

function spreadsheet_htmlentities(str)
{
	var tmp = '';
	while (tmp != str)
	{
		tmp = str;
		str = str.replace('"', '&quot;');
		str = str.replace('<', '&lt;');
		str = str.replace('<', '&gt;');
	}
	return str;
}

function spreadsheet_update(i)
{
	// Hent ark
	var spreadsheet = spreadsheet_array[i];
	if (!spreadsheet) return;
	
	// Gennemløber rækker og kolonner og laver data
	var data = '';
	for (r = 0; r <= spreadsheet['row_count']; r++)
	{
		var line = '';
		for (c = 0; c <= spreadsheet['col_count']; c++)
		{
			if (c > 0) line += spreadsheet['col_split'];
			var elm = document.getElementById('spreadsheet_' + spreadsheet['id'] + '_' + r + '_' + c);
			if (elm.tagName.toLowerCase() == 'select')
			{
				// Select
				line += spreadsheet_htmlentities(spreadsheet_removeall(spreadsheet_removeall(elm.options[elm.selectedIndex].value, spreadsheet['col_split']), spreadsheet['row_split']));
			}
			else
			{
				// Tekst-felt
				line += spreadsheet_removeall(spreadsheet_removeall(elm.value, spreadsheet['col_split']), spreadsheet['row_split']).replace('"', '&quot;');
			}
		}
		if (spreadsheet_removeall(line, spreadsheet['col_split']) != '')
		{
			if (data != '') data += spreadsheet['row_split'];
			data += line;
		}
	}
	
	// Gemmer data i felt
	document.getElementById(spreadsheet['id']).value = data;
}

var spreadsheet_array = Array();
