<?php
/*
*Fichier : gpt4all.php
*Description : Requête SQL pour le dialogue avec gpt4all
*Auteur : Clara Brodard
*Version : 1.3
*/

// Démarre la session PHP pour gérer les variables de session
session_start();
// Définit le type de contenu de la réponse HTTP en JSON
header("Content-Type: application/json");
// Inclut le fichier de connexion à la base de données (db.php)
require_once '../includes/db.php';

// Essaye d'obtenir une instance PDO pour la connexion à la base de données
try {
  $pdo = getPDO();
} catch (PDOException $e) {
   // En cas d'erreur, retourne un message d'erreur JSON et stoppe l'exécution
  echo json_encode(['error' => 'Connexion DB échouée : ' . $e->getMessage()]);
  exit;
}

/**
* Vérifie si un ID de discussion existe dans la base de données.
*
* @param PDO $pdo Instance PDO de la connexion à la base de données.
* @param int $id Identifiant de la discussion à vérifier.
* @return bool Retourne true si l'ID existe dans la table t_discussion, sinon false.
*/
function isDiscussionIdValide($pdo, $id) {
  $stmt = $pdo->prepare("SELECT 1 FROM t_discussion WHERE PK_Discussion = ?");
  $stmt->execute([$id]);
  return $stmt->fetch() !== false; // Retourne true si la discussion existe, sinon false
}


// Récupère le corps de la requête HTTP en JSON et le décode en tableau PHP
$data = json_decode(file_get_contents("php://input"), true);

// Vérifie que la clé "messages" existe dans les données reçues et que c'est un tableau
if (!isset($data["messages"]) || !is_array($data["messages"])) {
  // Si non, retourne une erreur JSON et stoppe le script
  echo json_encode(["error" => "Aucun message fourni."]);
  exit;
}

// Si aucune discussion active en session ou si l'ID stocké n'est pas valide,
// crée une nouvelle discussion en base avec un nom basé sur la date/heure courante
if (!isset($_SESSION["discussion_id"]) || !isDiscussionIdValide($pdo, $_SESSION["discussion_id"])) {
  $stmt = $pdo->prepare("INSERT INTO t_discussion (Nom) VALUES (:nom)");
  $stmt->execute(["nom" => "Discussion " . date("Y-m-d H:i:s")]);
  // Stocke le nouvel ID de discussion en session
  $_SESSION["discussion_id"] = $pdo->lastInsertId();
}

// Récupère l'ID de la discussion active et le tableau des messages reçus
$discussionId = $_SESSION["discussion_id"];
$messages = $data["messages"];

// Récupère le plus grand ordre actuel des messages dans cette discussion pour ordonner correctement le prochain message
$stmt = $pdo->prepare("SELECT COALESCE(MAX(Ordre), 0) FROM t_message WHERE FK_Discussion = ?");
$stmt->execute([$discussionId]);
$lastOrder = (int)$stmt->fetchColumn();

// Prend le dernier message envoyé dans le tableau des messages
$dernierMsg = end($messages);

// Si ce dernier message a le rôle "user" (message utilisateur)
if ($dernierMsg['role'] === 'user') {
  // Calcul de l'ordre du message utilisateur
  $userOrder = $lastOrder + 1;
  // Prépare l'insertion du message utilisateur en base dans la table t_message
  $stmt = $pdo->prepare("
  INSERT INTO t_message (GPT, Ordre, Message, FK_Discussion)
  VALUES (0, :ordre, :message, :discussion)
  ");
  // Exécute l'insertion avec les valeurs correspondantes
  $stmt->execute([
    'ordre'      => $userOrder,
    'message'    => $dernierMsg['content'],
    'discussion' => $discussionId
  ]);
} else {
  // Si le dernier message n'est pas un message utilisateur (rôle 'user'),
  // on arrête le script en renvoyant la variable $response (probablement vide ici)
  echo $response;
  exit;
}

// URL locale du service GPT4All pour générer une réponse
$gptUrl = "http://localhost:4891/v1/chat/completions";

// Initialise une session cURL pour envoyer la requête à GPT4All
$ch = curl_init($gptUrl);

// Configure cURL pour retourner la réponse sous forme de chaîne (et pas l'afficher directement)
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Indique qu'on va envoyer une requête POST
curl_setopt($ch, CURLOPT_POST, true);
// Définit l'entête HTTP Content-Type à application/json
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
// Envoie les données JSON (messages reçus) au service GPT4All
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// Exécute la requête cURL et stocke la réponse dans $response
$response = curl_exec($ch);
// Ferme la session cURL
curl_close($ch);

// Enregistre la réponse brute dans un fichier de log pour débogage
file_put_contents('../logs/debug_gpt_response.txt', $response, FILE_APPEND); // écrit la réponse dans un fichier pour debug

// Décode la réponse JSON reçue en tableau PHP
$responseData = json_decode($response, true);
// Récupère le contenu du message généré par GPT (s'il existe)
$reply = $responseData["choices"][0]["message"]["content"] ?? null;

// Si la réponse GPT n'est pas vide
if (!empty($reply)) {
  // Calcule l'ordre du message GPT (juste après le message utilisateur)
  $gptOrder = $userOrder + 1;
  // Prépare l'insertion du message GPT en base
  $stmt = $pdo->prepare("
    INSERT INTO t_message (GPT, Ordre, Message, FK_Discussion)
    VALUES (1, :ordre, :message, :discussion)
  ");
  // Exécute l'insertion avec les valeurs correspondantes
  $stmt->execute([
    'ordre'      => $gptOrder,
    'message'    => $reply,
    'discussion' => $discussionId
  ]);
}

// Retourne la réponse complète reçue de GPT4All au format JSON
echo $response;
?>
