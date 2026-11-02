<?php
// Page de diagnostic : quels champs du calendrier d'une poule l'API FFBB accepte-t-elle ?
// URL : http://localhost/projetSiteMvc/api/test_poule.php?id=200000003054340
$racine = "..";
include_once "$racine/src/modeles/ffbb.php";

header('Content-Type: text/plain; charset=utf-8');

$idPoule = isset($_GET['id']) ? $_GET['id'] : '200000003054340';
if (!ctype_digit($idPoule)) {
    exit("id invalide");
}
$jeton = getJetonFFBB('key_dh');

$essais = array(
    'base' => array('rencontres.id', 'rencontres.date_rencontre', 'rencontres.nomEquipe1', 'rencontres.nomEquipe2'),
    'salle' => array('rencontres.id', 'rencontres.salle.libelle', 'rencontres.salle.commune.libelle', 'rencontres.salle.commune.codePostal'),
    'organismes' => array('rencontres.id', 'rencontres.idOrganismeEquipe1.nom', 'rencontres.idOrganismeEquipe1.code', 'rencontres.idOrganismeEquipe1.logo.id',
        'rencontres.idOrganismeEquipe2.nom', 'rencontres.idOrganismeEquipe2.code', 'rencontres.idOrganismeEquipe2.logo.id'),
    'engagements' => array('rencontres.id', 'rencontres.idEngagementEquipe1.idOrganisme.nom', 'rencontres.idEngagementEquipe1.idOrganisme.code',
        'rencontres.idEngagementEquipe1.idOrganisme.logo.id', 'rencontres.idEngagementEquipe2.idOrganisme.code'),
    'poule' => array('id', 'nom', 'rencontres.id', 'rencontres.idPoule.id', 'rencontres.competitionId.id'),
);

foreach ($essais as $nom => $champs) {
    $url = FFBB_API_URL . '/items/ffbbserver_poules/' . $idPoule . '?deep[rencontres][_limit]=3';
    foreach ($champs as $c) {
        $url .= '&fields[]=' . urlencode($c);
    }
    $reponse = appelerFFBB($url, array('Authorization: Bearer ' . $jeton));
    echo "===== $nom : ";
    if ($reponse === false) {
        echo "ERREUR " . $GLOBALS['ffbb_erreur'] . "\n\n";
    } else {
        echo "OK\n" . json_encode(isset($reponse['data']['rencontres'][0]) ? $reponse['data']['rencontres'][0] : $reponse['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
}
