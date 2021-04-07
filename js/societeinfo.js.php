<?php
/* Copyright (C) 2016-2019 Garcia MICHEL <garcia@soamichel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php';
} // From "custom" directory

$ajaxUrl = dol_buildpath('/societeinfo/ajax/moreinfo.php', 1);
header('Content-type: text/javascript; charset=UTF-8');
header('Cache-Control: no-cache');
?>

$(function() {
	$("#dialog").dialog({
		autoOpen: false,
		minWidth: 500
	});

	$(".moreinfo").click(function(e){
		e.preventDefault();

		$("#dialog").empty();
		$("#dialog").append('<p>Changement des informations en cours...</p>');
		$("#dialog").dialog('open');
		$.get('<?php echo $ajaxUrl ?>', {id: $(this).data('id')})
			.done(function(data){
				if(data.success){
					$("#dialog").empty();
					var table = $('<table class="border" width="100%">');

					table.append('<tr><td colspan="2" class="liste_titre">Informations générales</td></tr>');
					table.append('<tr><td>Nom</td><td>'+data.result.name+'</td></tr>');
					if(typeof data.result.commercial_name !== 'undefined')
						table.append('<tr><td>Nom commercial</td><td>'+data.result.commercial_name+'</td></tr>');
					if(typeof data.result.business_name !== 'undefined')
						table.append('<tr><td>Nom alternatif</td><td>'+data.result.business_name+'</td></tr>');
					table.append('<tr><td>Adresse</td><td>'+data.result.street+' '+data.result.postal_code+' '+data.result.city+', '+data.result.country_code+'</td></tr>');
					table.append('<tr><td>SIREN</td><td>'+data.result.registration_number+'</td></tr>');
					if(typeof data.result.ape_code !== 'undefined')
						table.append('<tr><td>NAF-APE</td><td>'+data.result.ape_code+'</td></tr>');
					if(typeof data.result.rcs_name !== 'undefined')
						table.append('<tr><td>RCS/RM</td><td>'+data.result.rcs_name+'</td></tr>');
					if(typeof data.result.vat_number !== 'undefined')
						table.append('<tr><td>Numéro de TVA</td><td>'+data.result.vat_number+'</td></tr>');
					if(typeof data.result.capital !== 'undefined')
						table.append('<tr><td>Capital social</td><td>'+data.result.capital+'</td></tr>');
					if(typeof data.result.website_url !== 'undefined')
						table.append('<tr><td>Site</td><td><a href="'+data.result.website_url+'" target="_blank">'+data.result.website_url+'</a></td></tr>');
					if(typeof data.result.phone_number !== 'undefined')
						table.append('<tr><td>Téléphone</td><td>'+data.result.phone_number+'</td></tr>');
					if(typeof data.result.activity !== 'undefined')
						table.append('<tr><td>Activités</td><td>'+data.result.activity+'</td></tr>');

					if(typeof data.result.contacts !== 'undefined'){
						table.append('<tr><td colspan="2" class="liste_titre">Contact(s)</td></tr>');
						data.result.contacts.forEach(function(contact){
							table.append('<tr><td colspan="2">'+contact.name+' ('+contact.role+')</td></tr>');
						});
					}

					$("#dialog").append(table);
				}else{
					$.jnotify(data.errorMessage, 'error');
					$("#dialog").dialog("close");
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown){
				$.jnotify(errorThrown, 'error');
			});
	});
});
