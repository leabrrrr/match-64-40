<?php
$css = "admin.css";
include "$racine/src/vues/entete.php";

$reussi = strpos($message, 'Erreur') === false;
?>

<div class="conteneur page">
    <a class="retour-sombre" href="index.php?action=admin">← Retour à l'administration</a>

    <section class="carte carte-formulaire-admin">
        <h1>Synchronisation FFBB</h1>

        <div class="alerte <?php echo $reussi ? 'alerte-succes' : 'alerte-erreur'; ?>">
            <?php echo $message; ?>
        </div>

        <p class="texte-aide">
            Tous les matchs seniors du 64 et du 40 des 30 prochains jours sont importés
            (le week-end à venir par la recherche FFBB, les semaines suivantes par le calendrier des poules).
            Un match déjà présent est mis à jour. Si le club organisateur est inscrit
            (même nom que sur la FFBB), le match est rattaché à son compte.
        </p>

        <div class="actions-formulaire">
            <a class="bouton" href="index.php?action=admin">Voir les matchs</a>
            <a class="bouton bouton-contour-sombre" href="index.php?action=sync_ffbb">Relancer la synchro</a>
            <a class="bouton bouton-contour-sombre" href="api/test_api.php">Tester l'API</a>
        </div>
    </section>
</div>

<?php include "$racine/src/vues/pied.php"; ?>
