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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

if (!$user->admin or empty($conf->societeinfo->enabled)) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');
if ($action == 'save') {
    $search_limit = intval(GETPOST('search_limit', 'int'));
    $show_bodacc = intval(GETPOST('show_bodacc', 'int'));
    if ($search_limit and $search_limit <= 25) {
        dolibarr_set_const($db, "SOCIETEINFO_KEY", GETPOST('key', 'alpha'), 'chaine', 0, "Clé d'API SocieteInfo.com", $conf->entity);
        dolibarr_set_const($db, "SOCIETEINFO_SOC_DEFAULT_TYPE", GETPOST('soc_default_type', 'int'), 'chaine', 0, "Type de tiers par défault lors de la création", $conf->entity);
        dolibarr_set_const($db, "SOCIETEINFO_ADD_CONTACTS", GETPOST('add_contacts', 'int'), 'chaine', 0, "Récupérer les contacts lors de la création", $conf->entity);
        dolibarr_set_const($db, "SOCIETEINFO_SEARCH_LIMIT", $search_limit, 'chaine', 0, "Nombre de résultat lors d'une recherche", $conf->entity);
        dolibarr_set_const($db, "SOCIETEINFO_SHOW_BODACC", $show_bodacc, 'chaine', 0, "Afficher ou pas l'onglet Annonces légales", $conf->entity);
    } else {
        setEventMessage('Le nombre de résultat est 10 au maximum.', 'errors');
    }

    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

$langs->load("societeinfo@societeinfo");

$title = $langs->trans("SISetupTitle");
llxHeader('', $title);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title, $linkback, 'setup');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder" width="100%">'."\n";

print '<tr class="liste_titre">';
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td >'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="impair">';
print '  <td align="left" class="fieldrequired">Clé API (<a href="https://societeinfo.com/tarifs/" target="_blank">Plus d\'info</a>)</td>';
print '  <td>';
print '    <input type="account" name="key" value="'.$conf->global->SOCIETEINFO_KEY.'" required>';
print '  </td>';
print '</tr>';

print '<tr class="impair">';
print '  <td align="left" class="fieldrequired">Type de tiers par défaut lors de la création</td>';
print '  <td>';
print '    <select name="soc_default_type">';
print '     <option value="1" '.(intval($conf->global->SOCIETEINFO_SOC_DEFAULT_TYPE) == 1 ? 'selected' : '').'>Client</option>';
print '     <option value="2" '.(intval($conf->global->SOCIETEINFO_SOC_DEFAULT_TYPE) == 2 ? 'selected' : '').'>Prospect</option>';
print '     <option value="3" '.(intval($conf->global->SOCIETEINFO_SOC_DEFAULT_TYPE) == 3 ? 'selected' : '').'>Client/Prospect</option>';
print '    </select>';
print '  </td>';
print '</tr>';

print '<tr class="pair">';
print ' <td align="left" class="fieldrequired">Ajouter les contacts lors de la création</td>';
print ' <td>';
print '   <select name="add_contacts">';
print '     <option value="0" '.(intval($conf->global->SOCIETEINFO_ADD_CONTACTS) == 0 ? 'selected' : '').'>Non</option>';
print '     <option value="1" '.(intval($conf->global->SOCIETEINFO_ADD_CONTACTS) == 1 ? 'selected' : '').'>Oui</option>';
print '   </select>';
print ' </td>';
print '</tr>';

print '<tr class="impair">';
print '  <td align="left" class="fieldrequired">Nombre de résultat lors d\'une recherche (10 au maximum par défaut)</td>';
print '  <td>';
print '    <input type="account" name="search_limit" value="'.($conf->global->SOCIETEINFO_SEARCH_LIMIT ? $conf->global->SOCIETEINFO_SEARCH_LIMIT : '10').'" required>';
print '  </td>';
print '</tr>';

print '<tr class="pair">';
print '  <td align="left" class="fieldrequired">Afficher l\'onglet "Annonces légales"</td>';
print '  <td>';
print '    <select name="show_bodacc">';
print '     <option value="0" '.(intval($conf->global->SOCIETEINFO_SHOW_BODACC) == 0 ? 'selected' : '').'>Non</option>';
print '     <option value="1" '.(intval($conf->global->SOCIETEINFO_SHOW_BODACC) == 1 ? 'selected' : '').'>Oui</option>';
print '    </select>';
print '  </td>';
print '</tr>';

print '</table><br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Modify").'"></center>';

print '</form>';

llxFooter();
