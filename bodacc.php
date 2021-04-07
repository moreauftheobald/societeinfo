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

if (false === (@include '../main.inc.php')) { // From htdocs directory
    require '../../main.inc.php';
} // From "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/societeinfo/class/SocieteInfo.class.php');

$socid = GETPOST('socid', 'int');
if ($user->societe_id) {
    $socid=$user->societe_id;
}

$object = new Societe($db);
$result=$object->fetch($socid);
if ($result <= 0) {
    dol_print_error('', $object->error);
}

$langs->load("societeinfo@societeinfo");

if (!empty($object->idprof1)) {
    $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
    try {
        $query = $service->bodacc($object->idprof1);
    } catch (Exception $e) {
        setEventMessage($langs->trans($e->getMessage()), 'errors');
    }
}

llxHeader('', 'Annonces légales');

$head = societe_prepare_head($object);
dol_fiche_head($head, 'bodacc', $langs->trans("ThirdParty"), 0, 'company');
if (version_compare(DOL_VERSION, '3.9.0') >= 0) {
    dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
}

if (isset($query)) {
    print '<div class="fichecenter">';

    $pdate = new DateTime();
    $result = array_reverse($query->result);
    foreach ($result as $ann) {
        $pdate->setTimestamp($ann->parution_date/1000);

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';

        print '<tr><td>Catégorie d\'annonce</td><td>'.$ann->bodacc_type.'</td></tr>';
        print '<tr><td>Date de publication</td><td>'.$pdate->format('d/m/Y').'</td></tr>';
        print '<tr><td>Annonce n°</td><td>'.$ann->number.'</td></tr>';
        print '<tr><td>N°<abbr title="Registre du commerce et des sociétés">RCS</abbr></td><td>'.$ann->rcs_name.'</td></tr>';
        print '<tr><td>Dénomination sociale</td><td>'.$ann->legal_name.'</td></tr>';
        print '<tr><td>Forme jurique</td><td>'.$ann->legal_status.'</td></tr>';

        print '</table>';
        print '<br>';
    }

    print '</div>';
}

dol_fiche_end();

llxFooter();
