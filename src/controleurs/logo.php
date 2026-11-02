<?php
/*
 * CONTROLEUR : affiche le logo d'un club ou d'une division
 * Adresse : index.php?action=logo&id=<uuid>
 *
 * Le serveur de la FFBB refuse les requêtes qui viennent d'un navigateur :
 * on ne peut pas mettre son adresse directement dans un <img>.
 * Cette page sert donc d'intermédiaire : elle télécharge l'image côté PHP
 * (avec le bon User-Agent), la garde dans le dossier cache/logos/,
 * puis la renvoie au navigateur. Les fois suivantes, elle relit le fichier.
 */

include_once "getRacine.php";
include_once "$racine/src/modeles/ffbb.php";

$id = isset($_GET['id']) ? $_GET['id'] : '';

// On n'accepte qu'un identifiant au bon format (sécurité : il sert de nom de fichier)
if (!identifiantLogoValide($id)) {
    http_response_code(404);
    exit;
}

$dossier = "$racine/cache/logos";
$fichier = "$dossier/$id";

// Un échec (fichier vide) est retenté au bout d'un jour
$echecAncien = is_file($fichier) && filesize($fichier) == 0 && time() - filemtime($fichier) > 86400;

// Pas encore en cache → on le télécharge une fois
if (!is_file($fichier) || $echecAncien) {
    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }

    $image = telechargerLogoFFBB($id);
    $contenu = ($image === false) ? '' : $image['contenu']; // vide = "pas de logo"

    // On écrit dans un fichier temporaire puis on le renomme d'un coup :
    // si le même logo est demandé deux fois en même temps, la 2e demande
    // ne lira jamais un fichier à moitié écrit.
    $temporaire = $fichier . '.' . uniqid() . '.tmp';
    file_put_contents($temporaire, $contenu);
    rename($temporaire, $fichier);
    clearstatcache(); // PHP garde en mémoire l'ancienne taille du fichier : on l'efface
}

// Fichier vide = pas de logo : le navigateur affichera les initiales (voir script.js)
if (filesize($fichier) == 0) {
    http_response_code(404);
    exit;
}

// On devine le type d'image grâce à ses premiers octets
$debut = file_get_contents($fichier, false, null, 0, 16);
$type = 'image/png';
if (strncmp($debut, "\xFF\xD8\xFF", 3) === 0) {
    $type = 'image/jpeg';
} else if (strncmp($debut, "RIFF", 4) === 0) {
    $type = 'image/webp';
} else if (stripos($debut, '<svg') !== false || stripos($debut, '<?xml') !== false) {
    $type = 'image/svg+xml';
}

header('Content-Type: ' . $type);
// Un logo ne change presque jamais : le navigateur peut le garder 30 jours
header('Cache-Control: public, max-age=2592000');
readfile($fichier);
exit;

?>
