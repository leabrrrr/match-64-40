<?php
$css = "pageClub.css";
include "$racine/src/vues/entete.php";

// Nombre de matchs dont les infos sont encore à compléter
$aCompleter = 0;
foreach ($matchs as $match) {
    if (!$match['infos_remplies']) {
        $aCompleter++;
    }
}
?>

<div class="conteneur page">

    <div class="titre-page">
        <div>
            <p class="surtitre-sombre">Espace club</p>
            <h1><?php echo htmlspecialchars($club['nom'] ?? ''); ?></h1>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <span class="stat-valeur"><?php echo count($matchs); ?></span>
            <span class="stat-libelle">Matchs à domicile</span>
        </div>
        <div class="stat <?php if ($aCompleter > 0) { echo 'stat-alerte'; } ?>">
            <span class="stat-valeur"><?php echo $aCompleter; ?></span>
            <span class="stat-libelle">Infos à compléter</span>
        </div>
    </div>

    <section class="carte carte-tableau">
        <h2>Mes matchs</h2>

        <?php if (!empty($matchs)) { ?>
            <div class="tableau-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Match</th>
                            <th>Lieu</th>
                            <th>Infos pratiques</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($matchs as $match) { ?>
                            <tr>
                                <td class="cellule-date">
                                    <strong><?php echo date('d/m/Y', strtotime($match['date_match'])); ?></strong>
                                    <span><?php echo heure($match['heure_match']); ?></span>
                                </td>
                                <td class="cellule-match">
                                    <?php echo htmlspecialchars($match['equipe_domicile']); ?>
                                    <span class="vs">vs</span>
                                    <?php echo htmlspecialchars($match['equipe_exterieur']); ?>
                                    <span class="sous-texte"><?php echo htmlspecialchars($match['competition'] ?? ''); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($match['lieu']); ?></td>
                                <td>
                                    <?php if ($match['infos_remplies']) { ?>
                                        <span class="badge badge-vert">Remplies</span>
                                    <?php } else { ?>
                                        <span class="badge badge-attente">À compléter</span>
                                    <?php } ?>
                                </td>
                                <td class="cellule-action">
                                    <a class="bouton bouton-petit" href="index.php?action=modifier_match&id=<?php echo $match['id_match']; ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="vide-petit">
                Aucun match pour votre club. Les matchs sont récupérés depuis la FFBB par l'administrateur.
            </div>
        <?php } ?>
    </section>

</div>

<?php include "$racine/src/vues/pied.php"; ?>
