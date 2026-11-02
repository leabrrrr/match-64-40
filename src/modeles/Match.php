<?php
include_once "database.php";

function getAllMatchs(){
    $pdo = connexionPDO();
    $query = "SELECT * from matchs ORDER BY date_match, heure_match";
    $params = [];

    return fetchAll($pdo, $query, $params);
 }

function getMatchById($id_match){
    $pdo = connexionPDO();
    $query = "SELECT * from matchs WHERE id_match = ?";
    $params = [$id_match];

    return fetchOne($pdo, $query, $params);
 }

function getMatchsByClubId($id_club){
    $pdo = connexionPDO();
    $query = "SELECT * from matchs 
    WHERE id_club = ? ORDER BY date_match, heure_match";
    $params = [$id_club];

    return fetchAll($pdo, $query, $params);
}

// Ajout d'un match à la main (page admin "Ajouter un match")
function createMatch($equipe_domicile, $equipe_exterieur, $date_match, $heure_match, $lieu, $ffbb_id, $id_club, $competition = '', $departement = '64', $club_domicile = ''){
    $pdo = connexionPDO();

    $query = "INSERT INTO matchs (equipe_domicile, equipe_exterieur, date_match, heure_match, lieu, ffbb_id, id_club, competition, departement, club_domicile) 
    VALUES (?,?,?,?,?,?,?,?,?,?)";
    $params = [$equipe_domicile, $equipe_exterieur, $date_match, $heure_match, $lieu, $ffbb_id, $id_club, $competition, $departement, $club_domicile];

    return executeQuery($pdo, $query, $params);
}

function updateMatch($id_match, $equipe_domicile, $equipe_exterieur, $date_match, $heure_match, $lieu, $ffbb_id, $id_club, $competition = ''){
    $pdo = connexionPDO();

    $query = "UPDATE matchs SET equipe_domicile = ?, equipe_exterieur = ?, date_match = ?, heure_match = ?, lieu = ?, ffbb_id = ?, id_club = ?, competition = ?
    WHERE id_match = ?";
    $params = [$equipe_domicile, $equipe_exterieur, $date_match, $heure_match, $lieu, $ffbb_id, $id_club, $competition, $id_match];

    return executeQuery($pdo, $query, $params);
}

function getMatchsByDate($date) {
    $pdo = connexionPDO();
    $query = "SELECT * FROM matchs WHERE date_match = ? ORDER BY heure_match";
    $params = [$date];

    return fetchAll($pdo, $query, $params);
}

// Matchs entre deux dates, avec des filtres facultatifs :
//   $filtres['q']      : mot cherché dans les équipes, le lieu ou la compétition
//   $filtres['niveau'] : "Départemental", "Régional" ou "National"
//   $filtres['club']   : nom d'un club (à domicile ou à l'extérieur)
//   $filtres['zone']   : "ici" = joués dans le 64/40, "ailleurs" = joués hors du 64/40
function getMatchsByDateRange($date_debut, $date_fin, $filtres = array()) {
    $pdo = connexionPDO();
    $query = "SELECT * FROM matchs WHERE date_match >= ? AND date_match <= ?";
    $params = [$date_debut, $date_fin];

    if (!empty($filtres['q'])) {
        $query .= " AND (equipe_domicile LIKE ? OR equipe_exterieur LIKE ? OR lieu LIKE ? OR competition LIKE ?)";
        $mot = "%" . $filtres['q'] . "%";
        $params[] = $mot;
        $params[] = $mot;
        $params[] = $mot;
        $params[] = $mot;
    }

    if (!empty($filtres['niveau'])) {
        $query .= " AND niveau = ?";
        $params[] = $filtres['niveau'];
    }

    if (!empty($filtres['club'])) {
        $query .= " AND (club_domicile = ? OR club_exterieur = ?)";
        $params[] = $filtres['club'];
        $params[] = $filtres['club'];
    }

    if (isset($filtres['zone']) && $filtres['zone'] == 'ici') {
        $query .= " AND departement IN ('64', '40')";
    }
    if (isset($filtres['zone']) && $filtres['zone'] == 'ailleurs') {
        $query .= " AND departement NOT IN ('64', '40')";
    }

    $query .= " ORDER BY date_match, heure_match";
    return fetchAll($pdo, $query, $params);
}

// Liste des clubs du 64/40 (ceux qui reçoivent au moins un match dans le 64/40),
// pour le menu déroulant de recherche
function getClubs6440() {
    $pdo = connexionPDO();
    $query = "SELECT DISTINCT club_domicile FROM matchs
              WHERE departement IN ('64', '40') AND club_domicile <> ''
              ORDER BY club_domicile";

    $clubs = array();
    foreach (fetchAll($pdo, $query, []) as $ligne) {
        $clubs[] = $ligne['club_domicile'];
    }
    return $clubs;
}

// Renvoie le match qui a cet identifiant FFBB, ou false s'il n'est pas dans notre base
function getMatchByFfbbId($ffbb_id){
    $pdo = connexionPDO();
    $query = "SELECT * from matchs WHERE ffbb_id = ?";
    $params = [$ffbb_id];

    return fetchOne($pdo, $query, $params);
}

// Tous les matchs à partir d'aujourd'hui ($filtres : voir getMatchsByDateRange)
function getMatchsAVenir($filtres = array()){
    return getMatchsByDateRange(date('Y-m-d'), '2100-01-01', $filtres);
}

// Ajoute un match venant de la FFBB.
// $m est le tableau renvoyé par convertirRencontre() (voir ffbb.php)
function createMatchFFBB($m, $id_club){
    $pdo = connexionPDO();

    $query = "INSERT INTO matchs (equipe_domicile, equipe_exterieur, club_domicile, club_exterieur, date_match, heure_match,
        lieu, departement, competition, niveau, id_poule, nom_poule, logo_domicile, logo_exterieur, logo_competition, ffbb_id, id_club)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $params = [$m['domicile'], $m['exterieur'], $m['club_domicile'], $m['club_exterieur'], $m['date'], $m['heure'],
        $m['lieu'], $m['departement'], $m['competition'], $m['niveau'], $m['id_poule'], $m['nom_poule'],
        $m['logo_domicile'], $m['logo_exterieur'], $m['logo_competition'], $m['ffbb_id'], $id_club];

    return executeQuery($pdo, $query, $params);
}

// Met à jour un match venant de la FFBB (l'horaire ou la salle ont pu changer)
function updateMatchFFBB($id_match, $m, $id_club){
    $pdo = connexionPDO();

    $query = "UPDATE matchs SET equipe_domicile = ?, equipe_exterieur = ?, club_domicile = ?, club_exterieur = ?,
        date_match = ?, heure_match = ?, lieu = ?, departement = ?, competition = ?, niveau = ?,
        id_poule = ?, nom_poule = ?, logo_domicile = ?, logo_exterieur = ?, logo_competition = ?, id_club = ?
    WHERE id_match = ?";
    $params = [$m['domicile'], $m['exterieur'], $m['club_domicile'], $m['club_exterieur'],
        $m['date'], $m['heure'], $m['lieu'], $m['departement'], $m['competition'], $m['niveau'],
        $m['id_poule'], $m['nom_poule'], $m['logo_domicile'], $m['logo_exterieur'], $m['logo_competition'], $id_club, $id_match];

    return executeQuery($pdo, $query, $params);
}
