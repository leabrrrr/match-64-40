<?php

//Connexion a la bd
function connexionPDO() {
    $login = "root";
    $mdp = "";
    $bd = "projetsitemvcmatchs";
    $serveur = "localhost";

    try {
        $conn = new PDO("mysql:host=$serveur;dbname=$bd", $login, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'')); 
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        print "Erreur de connexion PDO ";
        die();
    }
}

//fonction requetes
//executer une requete
function executeQuery($pdo, $query, $params = []) {
    try {
        $req = $pdo->prepare($query);
        $req->execute($params);
    } catch (PDOException $e) {
        print "Erreur !: " .$e->getMessage();
        die();
    }
}

//Préparer et exécuter une requête, récupérer 1 seule ligne
function fetchOne($pdo, $query, $params = []) {
    try {
        $req = $pdo->prepare($query);
        $req->execute($params);

        $resultat = $req->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        print "Erreur !: " .$e->getMessage();
        die();
    }
    return $resultat;
}

//Préparer et exécuter une requête, récupérer TOUTES les lignes
function fetchAll($pdo, $query, $params = []) {
    $resultat = array();

    try {
        $req = $pdo->prepare($query);
        $req->execute($params);

        $ligne = $req->fetch(PDO::FETCH_ASSOC);
        while ($ligne) {
            $resultat[] = $ligne;
            $ligne = $req->fetch(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        print "Erreur !: " .$e->getMessage();
        die();
    }
    return $resultat;
}



?>