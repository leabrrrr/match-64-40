<?php
$css = "accueil.css";
include "$racine/src/vues/entete.php";
?>

<?php
// Les filtres en cours, à garder quand on change d'onglet
$lienFiltres = "&q=" . urlencode($filtres['q']) . "&niveau=" . urlencode($filtres['niveau']) . "&club=" . urlencode($filtres['club']);
?>

<section class="bandeau">
    <div class="conteneur">
        <!-- Grand titre : une ligne pleine, une ligne en contour -->
        <h1 class="titre-geant">
            <span class="ligne-pleine">Match</span>
            <!-- Le ballon sert de séparateur entre 64 et 40 -->
            <span class="ligne-contour">64<span class="ballon ballon-geant" aria-hidden="true"></span>40</span>
        </h1>

        <p class="etiquette">Seniors · Pyrénées-Atlantiques · Landes</p>
        <p class="bandeau-texte">
            Tous les matchs seniors du 64 et du 40, de la départementale à la nationale :
            horaires, salles, prix d'entrée et infos pratiques pour venir encourager votre club.
        </p>
    </div>
</section>

<div class="conteneur page">

    <!-- Les 3 onglets (on garde les filtres en cours) -->
    <div class="onglets">
        <a href="index.php?action=accueil&periode=weekend<?php echo $lienFiltres; ?>"
           class="onglet <?php if ($periode == 'weekend') { echo 'actif'; } ?>">Ce week-end</a>
        <a href="index.php?action=accueil&periode=tous<?php echo $lienFiltres; ?>"
           class="onglet <?php if ($periode == 'tous') { echo 'actif'; } ?>">Tous les matchs à venir</a>
        <a href="index.php?action=accueil&periode=exterieur<?php echo $lienFiltres; ?>"
           class="onglet <?php if ($periode == 'exterieur') { echo 'actif'; } ?>">Nos clubs à l'extérieur</a>
    </div>

    <!-- Barre de filtres : club, niveau, recherche libre -->
    <form class="barre-filtres" method="GET" action="index.php">
        <input type="hidden" name="action" value="accueil">
        <input type="hidden" name="periode" value="<?php echo $periode; ?>">

        <div class="filtre">
            <label for="club">Club</label>
            <select id="club" name="club">
                <option value="">Tous les clubs</option>
                <?php foreach ($lesClubs as $unClub) { ?>
                    <option value="<?php echo htmlspecialchars($unClub); ?>"
                        <?php if ($filtres['club'] == $unClub) { echo "selected"; } ?>><?php echo htmlspecialchars($unClub); ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="filtre">
            <label for="niveau">Niveau</label>
            <select id="niveau" name="niveau">
                <option value="">Tous les niveaux</option>
                <?php foreach ($lesNiveaux as $unNiveau) { ?>
                    <option value="<?php echo $unNiveau; ?>"
                        <?php if ($filtres['niveau'] == $unNiveau) { echo "selected"; } ?>><?php echo $unNiveau; ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="filtre filtre-large">
            <label for="q">Recherche</label>
            <input type="text" id="q" name="q" placeholder="Ville, salle, division..."
                   value="<?php echo htmlspecialchars($filtres['q']); ?>">
        </div>

        <div class="filtre-actions">
            <button type="submit" class="bouton">Filtrer</button>
            <?php if ($filtreActif) { ?>
                <a class="effacer" href="index.php?action=accueil&periode=<?php echo $periode; ?>">Effacer les filtres</a>
            <?php } ?>
        </div>
    </form>

    <div class="titre-section">
        <h2><?php echo $sousTitre; ?></h2>
        <span class="compteur"><?php echo count($matchs); ?> match<?php if (count($matchs) > 1) { echo "s"; } ?></span>
    </div>

    <?php if (!empty($matchs)) { ?>

        <?php foreach ($matchsParJour as $jour => $matchsDuJour) { ?>
            <h3 class="titre-jour"><?php echo dateLongue($jour); ?></h3>

            <div class="liste-matchs">
                <?php foreach ($matchsDuJour as $match) { ?>
                    <a class="carte-match" href="index.php?action=detail_match&id=<?php echo $match['id_match']; ?>">

                        <!-- En haut : heure, division et niveau -->
                        <div class="carte-haut">
                            <div class="ligne-heure">
                                <span class="carte-heure"><?php echo heure($match['heure_match']); ?></span>
                                <?php if ($match['niveau'] != '') { ?>
                                    <span class="<?php echo classeNiveau($match['niveau']); ?>"><?php echo htmlspecialchars($match['niveau']); ?></span>
                                <?php } ?>
                            </div>
                            <div class="ligne-division">
                                <?php echo logo($match['logo_competition'], $match['competition'], 'logo-division'); ?>
                                <span class="competition"><?php echo htmlspecialchars($match['competition']); ?></span>
                            </div>
                        </div>

                        <!-- Au milieu : les deux équipes face à face -->
                        <div class="face-a-face">
                            <div class="equipe-bloc">
                                <?php echo logo($match['logo_domicile'], $match['equipe_domicile'], 'logo-club'); ?>
                                <span class="equipe"><?php echo htmlspecialchars($match['equipe_domicile']); ?></span>
                            </div>
                            <span class="vs">VS</span>
                            <div class="equipe-bloc">
                                <?php echo logo($match['logo_exterieur'], $match['equipe_exterieur'], 'logo-club'); ?>
                                <span class="equipe"><?php echo htmlspecialchars($match['equipe_exterieur']); ?></span>
                            </div>
                        </div>

                        <!-- En bas : la salle et le bouton -->
                        <p class="meta">
                            <?php if ($periode == 'exterieur') { ?>
                                <span class="badge-deplacement">Déplacement</span>
                            <?php } ?>
                            <?php echo htmlspecialchars($match['lieu']); ?>
                        </p>
                        <span class="voir">Infos pratiques</span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>

    <?php } else { ?>
        <div class="vide">
            <div class="ballon ballon-grand"></div>
            <h3>Aucun match trouvé</h3>
            <?php if ($filtreActif) { ?>
                <p>Aucun match ne correspond à ces filtres.</p>
                <a class="bouton" href="index.php?action=accueil&periode=<?php echo $periode; ?>">Effacer les filtres</a>
            <?php } else { ?>
                <p>Il n'y a pas de match pour cette période.</p>
                <?php if ($periode == 'weekend') { ?>
                    <a class="bouton" href="index.php?action=accueil&periode=tous">Voir tous les matchs à venir</a>
                <?php } ?>
            <?php } ?>
        </div>
    <?php } ?>

</div>

<?php include "$racine/src/vues/pied.php"; ?>
