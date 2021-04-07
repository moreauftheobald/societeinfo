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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

dol_include_once('/societeinfo/class/SocieteInfo.class.php');

class ActionsSocieteInfo
{
    protected $db;

    public function ActionsSocieteInfo($db)
    {
        $this->db = $db;
    }

    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf, $user, $bc;

        if (!$user->rights->societe->creer) {
            return;
        }
        $langs->load("societeinfo@societeinfo");

        if ($action == 'majinfo_confirm' and $user->rights->societeinfo->majinfo) {
            $id = GETPOST('socid', 'int');
            $object->fetch($id);

            if ($object->idprof2 or $object->idprof1) {
                $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
                try {
                    $registration_number = $object->idprof2 ? $object->idprof2 : $object->idprof1;
                    $query = $service->getcompanybysiren($registration_number);
                } catch (Exception $e) {
                    setEventMessage($langs->trans($e->getMessage()), 'errors');
                    header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$object->id);
                    exit;
                }

                $object->name = $query->result->name;
                $object->name_alias = $query->result->commercial_name ? $query->result->commercial_name.', '.$query->result->business_name : $query->result->business_name;
                $object->address = $query->result->street;
                $object->zip = $query->result->postal_code;
                $object->town = $query->result->city;
                if ($query->result->country_code) {
                    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country WHERE code = '".$query->result->country_code."'";
                    $resql = $this->db->query($sql);
                    if ($resql and $this->db->num_rows($resql)) {
                        $object->country_id = intval($this->db->fetch_object($resql)->rowid);
                    } else {
                        $object->country_id = 0;
                    }
                } else {
                    $object->country_id = 0;
                }
                $object->phone = $query->result->phone_number;
                $object->email = (count($query->result->emails) > 0) ? $query->result->emails[0] : '';
                $object->url = $query->result->website_url;
                $object->idprof1 = $query->result->registration_number; // SIREN
                $object->idprof2 = $query->result->full_registration_number; // SIRET
                $object->idprof3 = $query->result->ape_code; // NAF-APE
                $object->idprof4 = $query->result->rcs_name; // RCS/RM
                $object->capital = $query->result->capital;
                $object->tva_intra = $query->result->vat_number;
                $object->status = ($query->result->legal_state == "Active") ? 1 : 0;

                $result = $object->update($object->id, $user);
                if ($result > 0) {
                    setEventMessage("Tiers actualisé");
                } else {
                    setEventMessage("Échec de mise à jour du tiers");
                }
            } else {
                setEventMessage("Le SIREN est nécessaire pour récuperer les informations du tiers.", 'errors');
            }

            header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$object->id);
            exit;
        }

        if ($action == 'createsoc') {
            $ids = GETPOST('ids', 'array');
            $registration_number = GETPOST('registration_number', 'alpha');

            if ($ids) {
                $created = 0;
                foreach ($ids as $id) {
                    $result = $this->create_societe($id);
                    if ($result > 0) {
                        $created++;
                    }
                }
                if (count($ids) == 1 and $result > 0) {
                    header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$result);
                    exit;
                } else {
                    setEventMessage($created.' tiers crées', 'mesgs');
                    header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
                    exit;
                }
            } elseif ($registration_number) {
                $result = $this->create_societe(null, $registration_number);
                if ($result > 0) {
                    header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$result);
                } else {
                    header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
                }
                exit;
            } else {
                header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
                exit;
            }
        }

        if ($action == 'search') {
            $query = GETPOST('query', 'alpha');
            $where = GETPOST('where', 'alpha');
            $searchMode = GETPOST('searchMode', 'alpha');
            $active = GETPOST('active', 'alpha') == 'on' ? true : false;
            $page = GETPOST('page', 'int') ? GETPOST('page', 'int') : 1;

            $this->displaySearchResult($query, $where, $searchMode, $page, $active);
        }

        if ($action == 'searchnext') {
            $query = GETPOST('query', 'alpha');
            $where = GETPOST('where', 'alpha');
            $searchMode = GETPOST('searchMode', 'alpha');
            $active = GETPOST('active', 'alpha') == 'on' ? true : false;
            $page = GETPOST('page', 'int') ? intval(GETPOST('page', 'int')) : 1;
            $page++;

            $this->displaySearchResult($query, $where, $searchMode, $page, $active);
        }

        $type = GETPOST('type', 'alpha');
        if ($action == "create" and !$type) {
            $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
            try {
                $query = $service->apikeyinfo();
                $apikeyinfo = $query->result;
            } catch (Exception $e) {
                setEventMessage($langs->trans($e->getMessage()), 'errors');
                header('Location: '.$_SERVER["PHP_SELF"].'?action=create&type=auto');
                exit;
            }

            $title=$langs->trans("ThirdParty");
            llxHeader('', $title);
            if (version_compare(DOL_VERSION, '3.8.0') >= 0) {
                print_fiche_titre($langs->trans("NewThirdParty"), "", 'title_companies.png');
            } else {
                print_fiche_titre($langs->trans("NewThirdParty"));
            }

            print '<p>Crédit(s) consommé(s) : '.$apikeyinfo->consumedCredits.' sur '.$apikeyinfo->maxCredits.' disponibles.</p>';

            print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            
            print '<p>Rechercher les informations du client sur <a href="http://societeinfo.com">societeinfo.com</a> ou <a href="'.$_SERVER['PHP_SELF'].'?action=create&type=auto">créer manuellement</a>.</p>';

            dol_fiche_head(null, 'card', '', 0, '');

            print '<table class="border" width="100%">';
            print '<tr><td class="fieldrequired">Quoi</td>';
            print '<td><input type="text" name="query" placeholder="mot clé..." required></td></tr>';

            print '<tr><td>Où</td>';
            print '<td><input type="text" name="where" placeholder="adresse de la societe (code postal, ville...)"></td></tr>';

            print '<tr><td>Recherche les sociétés actives uniquement ?</td>';
            print '<td><input type="checkbox" name="active"></td></tr>';

            print '<tr><td class="fieldrequired">Recherche par</td>';
            print '<td><select name="searchMode" required>';
            print '<option value="auto">Auto</option><option value="name">Nom</option><option value="legalname">Nom légal</option><option value="keyword">Activité</option><option value="person">Nom des dirigeants</option>';
            print '</select></td></tr>';
            print '</table>';

            dol_fiche_end();

            print '<div class="center">';
            print '<button type="submit" class="button" name="action" value="search">'.$langs->trans('Search').'</button>';
            print '</div>';

            print '</form>';

            print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

            print '<p>Créer directement un tier à partir de son SIREN ou son SIRET :</p>';

            dol_fiche_head(null, 'card', '', 0, '');

            print '<table class="border" width="100%">';
            print '<tr><td class="fieldrequired">SIREN/SIRET</td>';
            print '<td><input type="text" name="registration_number" placeholder="siren" required></td></tr>';
            print '</table>';

            dol_fiche_end();

            print '<div class="center">';
            print '<button type="submit" class="button" name="action" value="createsoc">'.$langs->trans('Create').'</button>';
            print '</div>';

            print '</form>';
            llxFooter();
            die();
        }
    }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user;

        if ($user->rights->societeinfo->majinfo) {
            print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=majinfo">Mettre à jour</a></div>';
        }

        if ($action == 'majinfo') {
            $form = new Form($this->db);
            print $form->formconfirm($_SERVER["PHP_SELF"].'?socid='.$object->id, 'Confirmer', 'Êtes-vous sûr de vouloir mettre à jour les informations de ce tiers ? <br><b style="color:red;">Attention!!! Cette action est irréversible!</b>', 'majinfo_confirm', '', 0, 1);
        }
    }

    public function displaySearchResult($search, $where, $searchMode, $page, $active)
    {
        global $conf, $langs, $bc;

        $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
        try {
            $query = $service->querysearch($search, $where, $searchMode, $page, $active, $conf->global->SOCIETEINFO_SEARCH_LIMIT);
        } catch (Exception $e) {
            setEventMessage($langs->trans($e->getMessage()), 'errors');
            header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
            exit;
        }

        $title=$langs->trans("ThirdParty");
        llxHeader('', $title, '', '', '', '', array('/societeinfo/js/societeinfo.js.php'), '', 0, 0);
        if (version_compare(DOL_VERSION, '3.8.0') >= 0) {
            print_fiche_titre($langs->trans("NewThirdParty"), "", 'title_companies.png');
        } else {
            print_fiche_titre($langs->trans("NewThirdParty"));
        }

        if ($query->total == 0) {
            print '<p>Aucune société trouvée.</p>';
            llxFooter();
            die();
        }

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="query" value="'.$search.'">';
        print '<input type="hidden" name="where" value="'.$where.'">';
        print '<input type="hidden" name="searchMode" value="'.$searchMode.'">';
        print '<input type="hidden" name="active" value="'.($active ? 'on' : 'off').'">';
        print '<input type="hidden" name="page" value="'.$page.'">';

        $maxpage = ceil($query->total/$conf->global->SOCIETEINFO_SEARCH_LIMIT);
        print '<p>'.$query->total.' société(s) trouvée(s). Page : ('.$page.' / '.$maxpage.').</p>';

        print '<p>Selectionner une ou plusieurs sociétés suivantes pour créer de nouveaux tiers</p>';

        print '<table class="border" width="100%">';
        print '<tr class="liste_titre"><td></td><td>'.$langs->trans("Name").'</td><td>SIREN</td><td>Activité</td><td>'.$langs->trans("Address").'</td><td></td></tr>';
        $var = true;
        foreach ($query->result as $result) {
            $var = !$var;
            print '<tr '.$bc[$var].'><td><input type="checkbox" name="ids[]" value="'.$result->id.'"></td><td>'.$result->name.'</td><td>'.$result->registration_number.'</td><td>'.$result->activity.'</td><td>'.$result->formatted_address.'</td><td><a href="#" data-id="'.$result->id.'" class="moreinfo">Plus d\'info</a></td></tr>';
        }
        print '</table><br>';

        print '<div class="center">';
        print '<button type="submit" class="butAction" name="action" value="createsoc">'.$langs->trans('Create').'</button>';
        if ($page < $maxpage) {
            print '<button type="submit" class="butAction" name="action" value="searchnext">Page suivante</button>';
        }
        print '</div>';

        print '</form>';

        print '<div id="dialog" title="Plus d\'info"></div>';

        llxFooter();
        die();
    }

    public function create_societe($id=null, $registration_number=null)
    {
        global $conf, $langs, $user;

        $service = new SocieteInfo($conf->global->SOCIETEINFO_KEY);
        $soc = new Societe($this->db);

        try {
            if ($id) {
                $query = $service->getcompanybyid($id);
            } elseif ($registration_number) {
                $query = $service->getcompanybysiren($registration_number);
            } else {
                setEventMessage('ID/SIREN manquant', 'errors');
                return -1;
            }
        } catch (Exception $e) {
            setEventMessage($langs->trans($e->getMessage()), 'errors');
            return -1;
        }

        $soc->name = $query->result->name;
        $soc->name_alias = $query->result->commercial_name ? $query->result->commercial_name.', '.$query->result->business_name : $query->result->business_name;
        $soc->address = $query->result->street;
        $soc->zip = $query->result->postal_code;
        $soc->town = $query->result->city;
        if ($query->result->country_code) {
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country WHERE code = '".$query->result->country_code."'";
            $resql = $this->db->query($sql);
            if ($resql and $this->db->num_rows($resql)) {
                $soc->country_id = intval($this->db->fetch_object($resql)->rowid);
            } else {
                $soc->country_id = 0;
            }
        } else {
            $soc->country_id = 0;
        }
        $soc->state_id = 0;
        $soc->phone = $query->result->phone_number;
        $soc->email = (is_array($query->result->emails) and count($query->result->emails) > 0) ? $query->result->emails[0] : '';
        $soc->url = $query->result->website_url;
        $soc->idprof1 = $query->result->registration_number; // SIREN
        $soc->idprof2 = $query->result->full_registration_number; // SIRET
        $soc->idprof3 = $query->result->ape_code; // NAF-APE
        $soc->idprof4 = $query->result->rcs_name; // RCS/RM
        $soc->capital = $query->result->capital;
        $soc->tva_intra = $query->result->vat_number;
        $soc->tva_assuj = 1;
        $soc->status = ($query->result->legal_state == "Active") ? 1 : 0;
        $soc->client = $conf->global->SOCIETEINFO_SOC_DEFAULT_TYPE;
        $soc->code_client = -1; // fix #6
        $soc->commercial_id = $user->id;

        $result = $soc->create($user);
        if ($result > 0) {
            // ajout des contacts
            if ($conf->global->SOCIETEINFO_ADD_CONTACTS and count($query->result->contacts) > 0) {
                foreach ($query->result->contacts as $c) {
                    $contact = new Contact($this->db);
                    $contact->socid = $result;
                    $aC = explode(',', $c->name, 2);
                    $contact->lastname = $aC[0];
                    $contact->firstname = isset($aC[1]) ? $aC[1] : '';
                    $contact->poste = $c->role;
                    $contact->create($user);
                }
            }

            return $result;
        } else {
            setEventMessage("Échec de création du tiers ".$query->result->name, 'errors');
            return -1;
        }
    }
}
