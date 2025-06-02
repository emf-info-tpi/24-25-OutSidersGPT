
<?php
/*
*Fichier : Get_discussions
*Description : Requête SQL pour récupérer toutes les discussions
*Auteur : Clara Brodard
*Version : 1.2
*/

header('Content-Type: application/json'); // Définit le type de contenu retourné comme JSON
// Inclusion du fichier de connexion à la base de données
require_once '../includes/db.php';

try {
    // Obtention d'une instance PDO via une fonction personnalisée
    $pdo = getPDO();
    // Exécution d'une requête SQL pour récupérer les discussions triées par identifiant décroissant
    $stmt = $pdo->query("SELECT PK_Discussion, Nom FROM t_discussion ORDER BY PK_Discussion DESC");
    // Récupération des résultats sous forme de tableau associatif
    $discussions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Envoi de la réponse JSON en cas de succès
    echo json_encode(['success' => true, 'discussions' => $discussions]);
} catch (PDOException $e) {
    // Gestion des erreurs PDO : envoi d'une réponse JSON avec le message d'erreur
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>