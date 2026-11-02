<?php
// Point d'accès direct à la synchro FFBB, renvoie du JSON.
// URL : http://localhost/projetSiteMvc/api/ffbb_sync.php
// Réservé à l'admin connecté (même session que le site).
session_start();

$racine = "..";
include_once "$racine/src/modeles/ffbb.php";
include_once "$racine/src/modeles/authentification.php";

header('Content-Type: application/json; charset=utf-8');

if (!estAdmin()) {
    http_response_code(403);
    echo json_encode(["succes" => false, "message" => "Accès réservé à l'administrateur"], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = syncFFBB();

echo json_encode([
    "succes" => strpos($message, "Erreur") === false,
    "message" => $message
], JSON_UNESCAPED_UNICODE);

?>
