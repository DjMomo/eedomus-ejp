<?php
/*************************************************************************************
**												
** Script PHP pour eedomus (toutes versions)
**
** Script qui permet d'afficher au format XML les données EJP d'une zone prédéfinie :
**  - état EJP du jour
**  - état EJP du lendemain
**  - décompte des jours EJP
**
*********
** Quelle que soit la donnée à afficher, un seul argument est à préciser dans l'URL
** d'appel du script, "NomZome" :
**           eedomus-ejp.php?zone="NomZone"
**
** L'argument "NomZone" devant être l'une des 4 zones EJP :
**                                      - nord,
**                                      - ouest,
**                                      - paca,
**                                      - sud.
**************************************************************************************/

// URL des pages à parser
$URL_histo = "https://particulier.edf.fr/services/rest/referentiel/historicEJPStore?searchType=ejp";
$URL_params = "https://particulier.edf.fr/services/rest/referentiel/getConfigProperty?PARAM_CONFIG_PROPERTY=";
$URL_etat = "https://particulier.edf.fr/bin/edf_rc/servlets/ejptemponew?TypeAlerte=EJP&Date_a_remonter=";

$str_min_zone = strtolower(GetArg("zone"));
$str_maj_zone = strtoupper($str_min_zone);

// Période de conservation des données en cache (en mn)
$validite_cache = 15;

$validite_cache = $validite_cache * 60;

// Date du jour au format demandé par l'API du site
$aujourdhui = date("Y-m-d");

$time_EJP_jour = loadVariable("time_EJP_jour");
$time_EJP_nb = loadVariable("time_EJP_nb");

// On interroge le site toutes les $validite_cache minutes minimum pour éviter les requêtes HTTP inutiles
if ((time() - $time_EJP_jour) > $validite_cache)
{
	$json_etat_EJP = jsonToXML(httpQuery($URL_etat.$aujourdhui));

	// Conversion de la première lettre en majsucule (à défaut de ucfirst())
	$str_zone = strtoupper(substr($str_min_zone,0,1)).substr($str_min_zone,1);

	// Extraction des EJP du jour et du lendemain
	$str_EJP_auj = xpath($json_etat_EJP,"//JourJ/Ejp".$str_zone);
	$str_EJP_dem = xpath($json_etat_EJP,"//JourJ1/Ejp".$str_zone);

	saveVariable("time_EJP_jour",time());
	saveVariable("str_EJP_auj",$str_EJP_auj);
	saveVariable("str_EJP_dem",$str_EJP_dem);
}
else
{
	// Rappel des valeurs précédemment sauvegardées
	$str_EJP_auj = loadVariable("str_EJP_auj");
	$str_EJP_dem = loadVariable("str_EJP_dem");
}

// On interroge le site toutes les $validite_cache miniutes minimum pour éviter les requêtes HTTP inutiles
if ((time() - $time_EJP_nb) > $validite_cache)
{
	$str_nb_total_jours = xpath(jsonToXML(httpQuery($URL_params."param.total.days.".$str_min_zone)),"param.total.days.".$str_min_zone);
	$str_EJP_nb = $str_nb_total_jours - xpath(jsonToXML(httpQuery($URL_histo)),"/".$str_maj_zone."/Total");

	saveVariable("time_EJP_nb",time());
	saveVariable("str_EJP_nb",$str_EJP_nb);
}
else
	$str_EJP_nb = loadVariable("str_EJP_nb");

// Génération du XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>';
$xml .= '<ejp>';
$xml .= '<aujourdhui>'.$str_EJP_auj.'</aujourdhui>';
$xml .= '<demain>'.$str_EJP_dem.'</demain>';
$xml .= '<decompte>'.$str_EJP_nb.'</decompte>';
$xml .= '</ejp>';
echo $xml;

?>
