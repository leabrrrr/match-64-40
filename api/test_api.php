<?php
// Page de diagnostic : vérifie que l'API FFBB fonctionne depuis WAMP.
// URL : http://localhost/projetSiteMvc/api/test_api.php
// Elle ne modifie rien dans la base, elle affiche seulement.

$racine = "..";
include_once "$racine/src/modeles/ffbb.php";

// Étape 1 : cURL est-il activé ?
$curlOk = function_exists('curl_init');

// Étape 2 : le jeton
$jeton = "";
$erreurJeton = "";
if ($curlOk) {
    $jeton = getJetonFFBB();
    $erreurJeton = $GLOBALS['ffbb_erreur'];
}

// Étape 3 : les rencontres des 7 prochains jours
$rencontres = false;
$erreurRencontres = "";
$matchs = array();
if ($jeton != "") {
    $debut = date('Y-m-d');
    $fin = date('Y-m-d', strtotime('+14 days')); // même période que la synchro
    $rencontres = getRencontresFFBB($debut, $fin);

    if ($rencontres === false) {
        $erreurRencontres = $GLOBALS['ffbb_erreur'];
    } else {
        // Même tri que la synchro : sans doublons, 64/40, dans la période
        foreach ($rencontres as $r) {
            if (concerne6440($r) && $r['date'] >= $debut && $r['date'] <= $fin && $r['senior']) {
                $matchs[$r['ffbb_id']] = $r;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API FFBB</title>
    <link rel="stylesheet" href="../public/css/header.css">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body>
<div class="conteneur page">
    <h1>Test de l'API FFBB</h1>
    <p class="texte-aide">Cette page ne modifie rien dans la base.</p>

    <section class="carte">
        <h2>1. Extension cURL</h2>
        <?php if ($curlOk) { ?>
            <span class="badge badge-vert">Activée</span>
        <?php } else { ?>
            <div class="alerte alerte-erreur">cURL est désactivée : clic gauche sur l'icône WAMP → PHP → Extensions PHP → cocher php_curl, puis redémarrer WAMP.</div>
        <?php } ?>
    </section>

    <section class="carte">
        <h2>2. Jeton (api.ffbb.app/items/configuration)</h2>
        <?php if ($jeton != "") { ?>
            <span class="badge badge-vert">Jeton récupéré</span>
        <?php } else if ($curlOk) { ?>
            <div class="alerte alerte-erreur">Erreur : <?php echo htmlspecialchars($erreurJeton); ?></div>
        <?php } ?>
    </section>

    <section class="carte">
        <h2>3. Rencontres du moteur de recherche (meilisearch-prod.ffbb.app)</h2>
        <?php if ($erreurRencontres != "") { ?>
            <div class="alerte alerte-erreur">Erreur : <?php echo htmlspecialchars($erreurRencontres); ?></div>
        <?php } else if ($rencontres !== false) { ?>
            <?php if ($GLOBALS['ffbb_avertissements'] > 0) { ?>
                <div class="alerte alerte-erreur"><?php echo $GLOBALS['ffbb_avertissements']; ?> recherche(s) n'ont pas répondu (même après un 2e essai).</div>
            <?php } ?>
            <div class="alerte alerte-succes">
                L'API fonctionne : <?php echo count($rencontres); ?> rencontres différentes reçues,
                <?php echo count($matchs); ?> matchs seniors différents concernant le 64/40
                (dont <?php $nb = 0; foreach ($matchs as $m) { if (!estDepartementSuivi($m['departement'])) { $nb++; } } echo $nb; ?> joués à l'extérieur).
            </div>
        <?php } ?>
    </section>

    <?php if (count($matchs) > 0) { ?>
        <section class="carte carte-tableau">
            <h2>Aperçu (30 premiers)</h2>
            <div class="tableau-scroll">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Match</th><th>Niveau</th><th>Logos</th><th>Club inscrit ?</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($matchs, 0, 30) as $r) { ?>
                            <tr>
                                <td class="cellule-date">
                                    <strong><?php echo date('d/m/Y', strtotime($r['date'])); ?></strong>
                                    <span><?php echo $r['heure'] !== null ? $r['heure'] : '--:--'; ?></span>
                                </td>
                                <td class="cellule-match">
                                    <?php echo htmlspecialchars($r['domicile']); ?>
                                    <span class="vs">vs</span>
                                    <?php echo htmlspecialchars($r['exterieur']); ?>
                                    <span class="sous-texte"><?php echo htmlspecialchars($r['competition']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($r['niveau']); ?></td>
                                <td>
                                    <?php foreach (array('logo_domicile', 'logo_exterieur', 'logo_competition') as $cle) { ?>
                                        <?php if ($r[$cle] !== null) { ?>
                                            <img src="../index.php?action=logo&id=<?php echo $r[$cle]; ?>" alt="" style="width:28px;height:28px;object-fit:contain">
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (trouverClub($r) !== null) { ?>
                                        <span class="badge badge-vert">Oui</span>
                                    <?php } else { ?>
                                        <span class="badge badge-gris">Non</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php } ?>

    <?php
    // État de la base, pour vérifier que bdd_complete.sql a bien été exécuté
    $pdo = connexionPDO();
    $colonnes = fetchAll($pdo, "SHOW COLUMNS FROM matchs", []);
    $nomsColonnes = array();
    foreach ($colonnes as $c) { $nomsColonnes[] = $c['Field'] . " (" . $c['Type'] . ")"; }
    $nbMatchs = fetchOne($pdo, "SELECT COUNT(*) AS nb, MIN(date_match) AS debut, MAX(date_match) AS fin, SUM(heure_match IS NULL) AS sansHeure FROM matchs", []);
    $lesClubs = fetchAll($pdo, "SELECT nom, role, valide FROM clubs", []);
    ?>
    <section class="carte">
        <h2>4. État de la base</h2>
        <p><strong>Colonnes de matchs :</strong> <?php echo htmlspecialchars(implode(', ', $nomsColonnes)); ?></p>
        <p><strong>Matchs en base :</strong> <?php echo $nbMatchs['nb']; ?> (du <?php echo $nbMatchs['debut']; ?> au <?php echo $nbMatchs['fin']; ?>, <?php echo $nbMatchs['sansHeure']; ?> sans heure)</p>
        <p><strong>Clubs :</strong>
            <?php foreach ($lesClubs as $c) { echo htmlspecialchars($c['nom'] . " [" . $c['role'] . ", valide=" . $c['valide'] . "]") . " ; "; } ?></p>
    </section>

    <?php if (is_array($rencontres) && count($rencontres) > 0) { ?>
        <section class="carte">
            <h2>5. Une rencontre lue par le site</h2>
            <pre style="white-space: pre-wrap; font-size: 12px;"><?php echo htmlspecialchars(json_encode(reset($rencontres), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </section>
    <?php } ?>

    <?php
    // 6. Classement de la poule du premier match reçu
    $premier = count($matchs) > 0 ? reset($matchs) : false;
    if ($premier && $premier['id_poule'] != '') {
        $poule = getPouleFFBB($premier['id_poule']);
    ?>
        <section class="carte">
            <h2>6. Poule de <?php echo htmlspecialchars($premier['domicile']); ?> (<?php echo htmlspecialchars($premier['nom_poule']); ?>)</h2>
            <?php if ($poule === false) { ?>
                <div class="alerte alerte-erreur">Erreur : <?php echo htmlspecialchars($GLOBALS['ffbb_erreur']); ?></div>
            <?php } else { ?>
                <div class="alerte alerte-succes">
                    <?php echo count($poule['classement']); ?> équipes au classement,
                    <?php echo count($poule['rencontres']); ?> rencontres dans la poule.
                </div>
                <pre style="white-space: pre-wrap; font-size: 12px;"><?php echo htmlspecialchars(json_encode(array_slice($poule['classement'], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <pre style="white-space: pre-wrap; font-size: 12px;"><?php echo htmlspecialchars(json_encode(array_slice($poule['rencontres'], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php } ?>
        </section>
    <?php } ?>

    <?php
    // 7. Calendrier des poules (les semaines suivantes), sur 10 poules
    $poulesTest = array();
    foreach ($matchs as $m) {
        if ($m['id_poule'] != '' && count($poulesTest) < 10) {
            $poulesTest[$m['id_poule']] = $m;
        }
    }
    if (count($poulesTest) > 0) {
        $calendrier = getCalendriersPoulesFFBB($poulesTest);
        $parDate = array();
        foreach ($calendrier as $r) {
            if ($r['date'] >= date('Y-m-d') && $r['date'] <= date('Y-m-d', strtotime('+30 days')) && concerne6440($r)) {
                $parDate[$r['date']] = isset($parDate[$r['date']]) ? $parDate[$r['date']] + 1 : 1;
            }
        }
        ksort($parDate);
    ?>
        <section class="carte">
            <h2>7. Calendrier de <?php echo count($poulesTest); ?> poules (30 prochains jours)</h2>
            <p><?php echo count($calendrier); ?> rencontres lues dans les calendriers. Matchs à venir concernant le 64/40, par date :</p>
            <ul>
                <?php foreach ($parDate as $jour => $nb) { ?>
                    <li><?php echo date('d/m/Y', strtotime($jour)); ?> : <?php echo $nb; ?> match(s)</li>
                <?php } ?>
            </ul>
        </section>
    <?php } ?>

    <a class="bouton" href="../index.php?action=admin">Retour au site</a>
</div>
</body>
</html>
