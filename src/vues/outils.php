<?php
// Petites fonctions d'affichage utilisées dans les vues

// "2026-09-26" → "sam."
function jourCourt($date) {
    $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    return $jours[date('w', strtotime($date))];
}

// "2026-09-26" → "sept."
function moisCourt($date) {
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return $mois[date('n', strtotime($date)) - 1];
}

// "2026-09-26" → "samedi 26 septembre 2026"
function dateLongue($date) {
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $t = strtotime($date);
    return $jours[date('w', $t)] . " " . date('j', $t) . " " . $mois[date('n', $t) - 1] . " " . date('Y', $t);
}

// "20:00:00" → "20h00" (et "--h--" si l'horaire n'est pas encore connu)
function heure($heure) {
    if ($heure === null || $heure === '') {
        return "--h--";
    }
    return substr($heure, 0, 2) . "h" . substr($heure, 3, 2);
}

// Affiche le logo d'un club ou d'une division.
// L'image passe par notre page index.php?action=logo (voir controleurs/logo.php).
// Si le logo n'existe pas, on affiche les initiales du nom à la place.
function logo($idLogo, $nom, $classe) {
    // Initiales : première lettre des deux premiers mots ("ASC PAU BC" → "AP")
    $mots = explode(' ', trim(preg_replace('/[^\p{L}0-9 ]/u', ' ', $nom)));
    $initiales = '';
    foreach ($mots as $mot) {
        if ($mot != '' && mb_strlen($initiales) < 2) {
            $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
        }
    }

    if ($idLogo == '') {
        return '<span class="' . $classe . ' initiales">' . htmlspecialchars($initiales) . '</span>';
    }

    // data-initiales sert à script.js si l'image ne se charge pas
    return '<img class="' . $classe . '" src="index.php?action=logo&id=' . urlencode($idLogo) . '"'
         . ' alt="" loading="lazy" data-initiales="' . htmlspecialchars($initiales) . '">';
}

// Vrai si deux noms désignent la même équipe, sans tenir compte du numéro :
// le classement écrit "LONS BASKET", les matchs "LONS BASKET - 2"
function memeEquipe($nom1, $nom2) {
    $nom1 = preg_replace('/ - \d+$/', '', trim($nom1));
    $nom2 = preg_replace('/ - \d+$/', '', trim($nom2));
    return strtoupper($nom1) == strtoupper($nom2);
}

// Classe CSS de la pastille de niveau : "Régional" → "niveau-regional"
function classeNiveau($niveau) {
    $sansAccent = str_replace(array('é', 'É'), 'e', mb_strtolower((string) $niveau));
    return 'niveau niveau-' . $sansAccent;
}

// 8.00 → "8,00 €" et 0 → "Gratuit"
function prix($prix) {
    if ($prix == 0) {
        return "Gratuit";
    }
    return number_format($prix, 2, ',', ' ') . " €";
}

?>
