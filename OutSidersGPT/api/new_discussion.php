<?php
/*
*Fichier : new_discussion.php
*Description : Requête SQL pour créer une nouvelle discussion
*Auteur : Clara Brodard
*Version : 1.2
*/

// Démarre la session PHP pour utiliser les variables de session
session_start(); 
// Définit le type de contenu de la réponse HTTP en JSON
header('Content-Type: application/json');
// Inclut le fichier de connexion à la base de données
require_once '../includes/db.php';

try {
    // Récupère une instance de connexion à la base de données via PDO
    $pdo = getPDO();
    // Génère un nom de discussion basé sur la date et l'heure actuelles
    $nom = 'Discussion ' . date('Y-m-d H:i:s');
    // Prépare une requête SQL pour insérer une nouvelle discussion avec ce nom
    $stmt = $pdo->prepare("INSERT INTO t_discussion (Nom) VALUES (:nom)");
    // Exécute la requête avec la valeur du nom
    $stmt->execute(['nom' => $nom]);

    // ✅ Stocke l'ID de la discussion créée
    $_SESSION['discussion_id'] = $pdo->lastInsertId();

    // Retourne une réponse JSON indiquant le succès de l'opération, ainsi que le nom de la discussion
    echo json_encode(['success' => true, 'message' => 'Discussion créée', 'nom' => $nom]);
} catch (PDOException $e) {
    // En cas d'erreur lors de la connexion ou de l'exécution de la requête, retourne une erreur JSON
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>

