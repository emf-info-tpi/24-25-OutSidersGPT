
<?php
/*
*Fichier : db.php
*Description : Création de la connexion avec la base de données
*Auteur : Clara Brodard
*Version : 1.1
*/
// Déclare une fonction nommée getPDO qui retourne une instance PDO connectée à la base de données
function getPDO() {
    // Paramètres de connexion à la base de données
    $host = 'localhost';      // Adresse du serveur de base de données
    $dbname = 'mydb';         // Nom de la base de données
    $username = 'root';       // Nom d'utilisateur MySQL
    $password = '';           // Mot de passe (vide ici, ce qui est courant en local mais à éviter en production)

    // Crée une nouvelle instance PDO pour se connecter à MySQL avec les paramètres fournis
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Configure PDO pour qu'il lève une exception en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retourne l'objet PDO ainsi configuré
    return $pdo;
}
