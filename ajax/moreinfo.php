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

dol_include_once('/societeinfo/class/SocieteInfo.class.php');

$langs->load("societeinfo@societeinfo");

$id = GETPOST('id', 'alpha');
if (empty($id)) {
    $res = array('success' => false, 'errorMessage' => 'ID manquant');
} else {
    $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
    try {
        $query = $service->getcompanybyid($id);
        $res = array(
            'success' => true,
            'errorMessage' => '',
            'result' => $query->result
        );
    } catch (Exception $e) {
        $res = array('success' => false, 'errorMessage' => $langs->trans($e->getMessage()));
    }
}

header('Content-type: application/json; charset=utf-8');
echo json_encode($res);
