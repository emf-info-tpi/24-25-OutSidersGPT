<?php
/*
*Fichier : get_gpts.php
*Description : Requête SQL pour récupérer les gpts personnalisés
*Auteur : Clara Brodard
*Version : 1.2
*/

// Définit le type de contenu de la réponse en JSON
header("Content-Type: application/json");
// Inclusion du fichier de connexion à la base de données
require_once '../includes/db.php';

try {
    $pdo = getPDO(); // Obtention de l'instance PDO via la fonction personnalisée
    // Exécution d'une requête SQL pour récupérer l'identifiant et le nom de chaque GPT, triés par identifiant décroissant
    $stmt = $pdo->query("SELECT PK_GPT, Nom FROM t_gpt ORDER BY PK_GPT DESC");
    // Récupération de tous les résultats sous forme de tableau associatif
    $gpts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Encodage en JSON de la réponse, indiquant le succès et les données récupérées
    echo json_encode(['success' => true, 'gpts' => $gpts]);
} catch (PDOException $e) {
    // En cas d'exception PDO, retourne une réponse JSON avec le message d'erreur
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>