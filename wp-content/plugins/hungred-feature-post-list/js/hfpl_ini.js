/*  Copyright 2009  Clay Lua  (email : clay@hungred.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
jQuery(document).ready(function() {
	/*
Name: ajaxCall
Usage: use to update the status of the record
Parameter: 	calller: determine who are the caller

Description: use to update the status of the record
*/
function ajaxCall(status, id)
{
	jQuery.post("../wp-content/plugins/hungred-feature-post-list/hfpl_update.php", { current: status, post: id}, function(data){
  });
}
	jQuery("#hfpl_checkbox").click(function(){
		if(jQuery("#hfpl_status").attr('value') == 'publish' && jQuery("#hfpl_id").attr('value') != "")
		ajaxCall(jQuery(this).attr('checked'), jQuery("#hfpl_id").attr('value'));
		else
		{
			jQuery(this).attr('checked', false);
			alert('Post must be published before it can be featured');
		}
	});
	
});