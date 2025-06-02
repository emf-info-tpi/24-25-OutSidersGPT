<?php
/*
*Fichier : save_gpt.php
*Description : Requête SQL pour créer un nouveau gpt personnalisé
*Auteur : Clara Brodard
*Version : 1.2
*/

// Définit le type de contenu de la réponse HTTP en JSON
header("Content-Type: application/json");
// Inclut le fichier de connexion à la base de données
require_once '../includes/db.php';

// Récupère le corps de la requête HTTP (JSON) et le convertit en tableau associatif PHP
$data = json_decode(file_get_contents("php://input"), true);

// Vérifie que le champ 'nom' existe et n'est pas vide après suppression des espaces
if (!isset($data['nom']) || trim($data['nom']) === '') {
    // Si le nom est manquant ou vide, retourne une erreur JSON et arrête l'exécution
    echo json_encode(['success' => false, 'message' => 'Nom manquant']);
    exit;
}

// Récupère le nom du GPT et le prompt (optionnel, par défaut chaîne vide)
$nom = $data['nom'];
$prompt = $data['prompt'] ?? '';


try {
    // Connexion à la base de données via PDO
    $pdo = getPDO();
    // Prépare une requête SQL pour insérer le nouveau GPT personnalisé
    $stmt = $pdo->prepare("INSERT INTO t_gpt (Nom, Prompt) VALUES (:nom, :prompt)");
    // Exécute la requête avec les valeurs fournies
    $stmt->execute([
        'nom' => $nom,
        'prompt' => $prompt
    ]);
    // Retourne une réponse JSON avec l'ID du GPT inséré et un indicateur de succès
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    // En cas d'erreur SQL ou de connexion, retourne une erreur JSON avec le message de l'exception
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>