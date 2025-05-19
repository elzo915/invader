<?php


// Connexion à la base de données avec PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=invader', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invader - Accueil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .header-bar {
            background: #d3d3d3;
            border-radius: 30px;
            display: flex;
            align-items: center;
            padding: 10px 40px 10px 20px;
            margin: 30px 20px 0 20px;
        }
        .logo {
            font-size: 60px;
            font-weight: bold;
            font-family: 'Arial Black', Arial, sans-serif;
            margin-right: 40px;
        }
        nav {
            display: flex;
            gap: 40px;
            width: 100%;
            justify-content: center;
        }
        nav a {
            font-size: 32px;
            font-weight: bold;
            color: #000;
            text-decoration: none;
            font-family: Arial, sans-serif;
            transition: text-decoration 0.2s;
        }
        nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <span class="logo">INVADER</span>
        <nav>
            <a href="produits.php">Produits</a>
            <a href="categories.php">categories</a>
            <a href="fournisseurs.php">fournisseurs</a>
            <a href="inventaires.php">inventaires</a>
        </nav>
    </div>
</body>
</html>