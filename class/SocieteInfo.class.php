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
class SocieteInfo
{
    private $url;
    private $key;

    public function __construct($key, $url="http://societeinfo.com/app/rest/api/v1")
    {
        $this->url = $url;
        $this->key = $key;
    }

    /**
     * Recherche par nom, activité, code APE et code postal ou ville.
     *
     * @param string $query nom de la société ou activité ou code APE
     * @param string $where code postal ou ville
     * @param string $searchMode type de recherche [auto,name,legalname,keyword,person]
     * @param integer $page numéro de la page (1 par défaut)
     * @param boolean $active limiter les résultats aux entreprises actives ou pas
     * @param integer $limit nombre de résultats par page (10 par défaut)
     *
     * @return object
     */
    public function querysearch($query, $where="", $searchMode="auto", $page=1, $active=false, $limit=10)
    {
        $url = $this->url.'/querysearch/companies/json?query='.urlencode($query).'&where='.urlencode($where).'&searchMode='.urlencode($searchMode).'&page='.urlencode($page).'&limit='.urlencode($limit).'&active='.($active ? 'true' : 'false').'&key='.urlencode($this->key);

        $query = file_get_contents($url);

        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }

    /**
     * Obtenir les informations détaillées d'une société par ID
     *
     * @param string $id id obtenu par une requête query search (cf fonction querysearch)
     *
     * @return object
     */
    public function getcompanybyid($id)
    {
        $url = $this->url.'/company/json?id='.urlencode($id).'&key='.urlencode($this->key);
        $query = file_get_contents($url);

        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }

    /**
     * Obtenir les informations détaillées d'une société par ID
     *
     * @param string $registration_number Numéro SIREN
     *
     * @return object
     */
    public function getcompanybysiren($registration_number)
    {
        $url = $this->url.'/company/json?registration_number='.urlencode($registration_number).'&key='.urlencode($this->key);
        $query = file_get_contents($url);

        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }

    /**
     * Obtenir toutes les sociétés liées à un nom de domaine : Mentions légales, Registrar...
     *
     * @param string $domain_name nom de domaine ou url
     * @return object
     */
    public function domainsearch($domain_name)
    {
        $url = $this->url.'/domainsearch/companies/json?domain_name='.urlencode($domain_name).'&key='.urlencode($this->key);
        $query = file_get_contents($url);

        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }

    /**
     * Obtenir l'ensemble des annonces légales du bodacc
     *
     * @param string $registration_number Numéro SIREN
     * @param string $id id obtenu par une requête query search (cf fonction querysearch)
     * @return object
     */
    public function bodacc($registration_number='', $id='')
    {
        if ($registration_number != '') {
            $url = $this->url.'/bodacc/json?key='.urlencode($this->key).'&registration_number='.urlencode($registration_number);
        } elseif ($id != '') {
            $url = $this->url.'/bodacc/json?key='.urlencode($this->key).'&id='.urlencode($id);
        } else {
            throw new Exception("Le SIREN ou l'ID doit être renseigné");
        }

        $query = file_get_contents($url);
        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }

    /**
     * Consulter crédits disponibles
     *
     * @return object
     */
    public function apikeyinfo()
    {
        $url = $this->url.'/apikeyinfo/json?key='.urlencode($this->key);
        $query = file_get_contents($url);

        if ($query === false) {
            throw new Exception('Echec de connexion au service societeinfo.com');
        }

        $query = json_decode($query);
        if ($query->success) {
            return $query;
        } else {
            throw new Exception($query->errorCode);
        }
    }
}
