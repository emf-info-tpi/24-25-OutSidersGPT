<?php
/*
*Fichier : load_messages.php
*Description : Requête SQL pour récupérer l'historique d'une discussion
*Auteur : Clara Brodard
*Version : 1.3
*/

// Démarre la session PHP pour gérer les variables de session
session_start();
// Définit le type de contenu de la réponse HTTP en JSON
header("Content-Type: application/json");
// Inclut le fichier contenant la fonction de connexion à la base de données
require_once '../includes/db.php';

// Vérifie si un paramètre 'id' est passé dans l'URL (via GET)
if (!isset($_GET['id'])) {
    // Si aucun 'id' fourni, retourne un JSON avec succès = false et message d'erreur, puis arrête le script
    echo json_encode(['success' => false, 'message' => 'Aucun ID fourni']);
    exit;
}

// Récupère l'ID de discussion depuis le paramètre GET et le convertit en entier
$discussionId = (int)$_GET['id'];
// Stocke cet ID dans la session sous la clé 'discussion_id'
$_SESSION["discussion_id"] = $discussionId;

try {
    // Obtient une connexion PDO à la base de données
    $pdo = getPDO();

    // Prépare une requête SQL pour récupérer les messages liés à cette discussion, triés par ordre croissant
    $stmt = $pdo->prepare("SELECT GPT, Message FROM t_message WHERE FK_Discussion = ? ORDER BY Ordre ASC");
    // Exécute la requête avec l'ID de discussion
    $stmt->execute([$discussionId]);
    // Récupère tous les résultats sous forme de tableau associatif
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourne un JSON avec succès = true et le tableau des messages
    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    // En cas d'erreur lors de la connexion ou de la requête, retourne un JSON avec succès = false et le message d'erreur
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>