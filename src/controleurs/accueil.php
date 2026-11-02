<?php

include_once "getRacine.php";
include_once "$racine/src/modeles/Club.php";
include_once "$racine/src/modeles/Match.php";

// Onglet choisi (?periode=...) :
//   "weekend"   → matchs du week-end dans le 64/40 (par défaut)
//   "tous"      → tous les matchs à venir dans le 64/40
//   "exterieur" → matchs des clubs du 64/40 joués hors du 64/40
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'weekend';

// Filtres de la barre de recherche (tous facultatifs)
$filtres = array(
    'q' => isset($_GET['q']) ? trim($_GET['q']) : '',
    'niveau' => isset($_GET['niveau']) ? $_GET['niveau'] : '',
    'club' => isset($_GET['club']) ? $_GET['club'] : '',
    'zone' => ($periode == 'exterieur') ? 'ailleurs' : 'ici'
);

if ($periode == 'tous') {
    $matchs = getMatchsAVenir($filtres);
    $sousTitre = "Tous les matchs à venir dans le 64/40";
} else if ($periode == 'exterieur') {
    $matchs = getMatchsAVenir($filtres);
    $sousTitre = "Les clubs du 64/40 en déplacement hors du département";
} else {
    $periode = 'weekend';

    // Trouver le prochain samedi et dimanche
    $aujourd_hui = new DateTime();
    $jour_semaine = $aujourd_hui->format('N'); // 1=lundi, 7=dimanche

    // Calculer le samedi (6)
    $samedi = clone $aujourd_hui;
    $samedi->modify('+' . (6 - $jour_semaine) . ' days');

    // Calculer le dimanche (7)
    $dimanche = clone $samedi;
    $dimanche->modify('+1 day');

    // Le vendredi soir compte aussi (beaucoup de matchs seniors ont lieu ce jour-là)
    $vendredi = clone $samedi;
    $vendredi->modify('-1 day');

    // Récupérer les matchs du weekend
    $date_debut = $vendredi->format('Y-m-d');
    $date_fin = $dimanche->format('Y-m-d');
    $matchs = getMatchsByDateRange($date_debut, $date_fin, $filtres);
    $sousTitre = "Matchs du week-end (" . $vendredi->format('d/m') . " - " . $dimanche->format('d/m') . ")";
}

// Listes pour les menus déroulants de la barre de filtres
$lesNiveaux = array('Départemental', 'Régional', 'National');
$lesClubs = getClubs6440();

// Y a-t-il un filtre actif ? (pour afficher le lien "Effacer les filtres")
$filtreActif = ($filtres['q'] != '' || $filtres['niveau'] != '' || $filtres['club'] != '');

// Regrouper les matchs par jour pour l'affichage : $matchsParJour['2026-09-26'] = [...]
$matchsParJour = array();
foreach ($matchs as $match) {
    $matchsParJour[$match['date_match']][] = $match;
}

// Inclure la vue
$titre = "Matchs de basket seniors du 64/40";
include "$racine/src/vues/accueil.php";

?>
