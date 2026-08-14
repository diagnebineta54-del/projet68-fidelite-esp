<?php
/**
 * Connexion à la base de données MySQL via PDO (requêtes préparées obligatoires)
 */

$DB_HOST = 'localhost';
$DB_NAME = 'fidelite_esp';
$DB_USER = 'root';
$DB_PASS = '';       // Par défaut XAMPP : mot de passe root vide
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées côté serveur
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Ne jamais afficher les détails de connexion en production
    die('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré dans XAMPP.');
}
