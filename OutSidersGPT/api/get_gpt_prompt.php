<?php
/*
*Fichier : get_gpt_prompt.php
*Description : Requête SQL pour récupérer un prompt précis
*Auteur : Clara Brodard
*Version : 1.3
*/

// Définit le type de contenu de la réponse comme JSON
header("Content-Type: application/json");
// Inclusion du fichier de connexion à la base de données
require_once '../includes/db.php';


// Vérifie que l'identifiant a bien été passé en paramètre GET
if (!isset($_GET['id'])) {
    // Retourne une erreur si l'ID est absent
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit; // Interrompt l'exécution du script
}

// Cast sécurisé de l'ID en entier
$id = (int)$_GET['id'];

try {
    $pdo = getPDO(); // Récupération de l'instance PDO via une fonction personnalisée
    // Préparation d'une requête paramétrée pour éviter les injections SQL
    $stmt = $pdo->prepare("SELECT Prompt FROM t_gpt WHERE PK_GPT = ?");
    // Exécution de la requête avec l'ID en paramètre
    $stmt->execute([$id]);
    // Récupère directement la première colonne du premier résultat
    $prompt = $stmt->fetchColumn();

    // Vérifie si un résultat a été trouvé
    if ($prompt === false) {
        // Réponse en cas d'ID inexistant
        echo json_encode(['success' => false, 'message' => 'GPT introuvable']);
    } else {
        // Réponse avec le prompt trouvé
        echo json_encode(['success' => true, 'prompt' => $prompt]);
    }
} catch (PDOException $e) {
    // Gestion des erreurs PDO, retourne un message d'erreur
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>