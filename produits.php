<?php
// ==========================
// Affichage des erreurs PHP pour le développement
// ==========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==========================
// Connexion à la base de données avec PDO (MySQL)
// ==========================
try {
    $pdo = new PDO('mysql:host=localhost;dbname=invader', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Affiche un message d'erreur si la connexion échoue
    die("Erreur de connexion : " . $e->getMessage());
}

// ==========================
// Définition des colonnes triables/filtrables
// ==========================
$colonnes_tri = [
    'Nom' => 'p.Nom',
    'categorie' => 'c.nom_categorie',
    'etat' => 'p.etat'
];

// ==========================
// Récupération de la colonne filtrée (si présente dans l'URL)
// ==========================
$filtre_colonne = isset($_GET['filtre_colonne']) && array_key_exists($_GET['filtre_colonne'], $colonnes_tri) ? $_GET['filtre_colonne'] : null;

// ==========================
// Récupération de la valeur du filtre (si présente dans l'URL)
// ==========================
$filtre_valeur = isset($_GET['filtre_valeur']) ? $_GET['filtre_valeur'] : null;

// ==========================
// Définition du tri par défaut ou selon l'URL
// ==========================
$tri = isset($_GET['tri']) && array_key_exists($_GET['tri'], $colonnes_tri) ? $_GET['tri'] : 'Nom';
$sens = (isset($_GET['sens']) && $_GET['sens'] === 'desc') ? 'desc' : 'asc';
$colonne_sql = $colonnes_tri[$tri];

// ==========================
// Récupération des listes pour les filtres (catégories, fournisseurs, noms produits, états)
// ==========================
$categories = $pdo->query("SELECT * FROM Categories")->fetchAll(PDO::FETCH_ASSOC);
$fournisseurs = $pdo->query("SELECT * FROM Fournisseurs")->fetchAll(PDO::FETCH_ASSOC);
$noms_produits = $pdo->query("SELECT DISTINCT Nom FROM Produits ORDER BY Nom ASC")->fetchAll(PDO::FETCH_COLUMN);
$etats = ['satisfaisant', 'intermediaire', 'critique'];

// ==========================
// Construction dynamique de la requête SQL selon le filtre ou le tri
// ==========================
$where = []; // Tableau pour stocker les conditions WHERE
$params = []; // Tableau pour stocker les valeurs des paramètres préparés

// Ajout des conditions de filtre si besoin
if ($filtre_colonne && $filtre_valeur !== null && $filtre_valeur !== '') {
    if ($filtre_colonne === 'Nom') {
        $where[] = 'p.Nom = ?';
        $params[] = $filtre_valeur;
    } elseif ($filtre_colonne === 'categorie') {
        $where[] = 'c.id_categorie = ?';
        $params[] = $filtre_valeur;
    } elseif ($filtre_colonne === 'etat') {
        $where[] = 'p.etat = ?';
        $params[] = $filtre_valeur;
    }
}

// Construction de la requête SQL principale
$sql = "
    SELECT p.id_produit, p.Nom, c.nom_categorie, c.id_categorie, p.Idf, p.etat, p.seuil_critique, p.stock_max, p.conditionnement, p.stock_actuel
    FROM Produits p
    JOIN Categories c ON p.id_categorie = c.id_categorie
";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY $colonne_sql $sens";

// Préparation et exécution de la requête SQL
$query = $pdo->prepare($sql);
$query->execute($params);
$produits = $query->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// Traitement du formulaire d'ajout de produit
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_produit'])) {
    // Récupération des champs du formulaire
    $nom = $_POST['nom'];
    $categorie = $_POST['categorie'];
    $fournisseur = $_POST['fournisseur'];
    $conditionnement = $_POST['conditionnement'];
    $seuil_critique = $_POST['seuil_critique'];
    $stock_max = $_POST['stock_max'];
    $stock_initial = $_POST['stock_initial'];
    // Vérification des champs obligatoires
    if (
        $nom !== '' && $categorie !== '' && $fournisseur !== '' &&
        $conditionnement !== '' && $seuil_critique !== '' &&
        $stock_max !== '' && $stock_initial !== ''
    ) {
        // Détermination de l'état selon le stock initial
        if ($stock_initial <= $seuil_critique) {
            $etat = 'critique';
        } elseif ($stock_initial <= $stock_max) {
            $etat = 'satisfaisant';
        } else {
            $etat = 'intermediaire';
        }
        try {
            // Insertion du nouveau produit dans la base de données
            $stmt = $pdo->prepare("INSERT INTO Produits (Nom, id_categorie, Idf, conditionnement, seuil_critique, stock_max, etat, stock_actuel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $categorie, $fournisseur, $conditionnement, $seuil_critique, $stock_max, $etat, $stock_initial]);
            header('Location: produits.php'); // Redirection après ajout
            exit;
        } catch (PDOException $e) {
            // Gestion des erreurs d'insertion
            $erreur_ajout = "Erreur lors de l'ajout : " . $e->getMessage();
            $_GET['add'] = 1;
        }
    } else {
        // Message d'erreur si un champ est vide
        $erreur_ajout = "Tous les champs sont obligatoires.";
        $_GET['add'] = 1;
    }
}

// ==========================
// Traitement du formulaire de modification ou suppression d'un produit
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_produit'])) {
    if (isset($_POST['supprimer'])) {
        // Suppression du produit
        $stmt = $pdo->prepare("DELETE FROM Produits WHERE id_produit = ?");
        $stmt->execute([$_POST['id_produit']]);
        header('Location: produits.php');
        exit;
    } else {
        // Modification du produit
        $stmt = $pdo->prepare("
            UPDATE Produits
            SET Nom = ?, id_categorie = ?, Idf = ?, conditionnement = ?, seuil_critique = ?, stock_max = ?, stock_actuel = ?
            WHERE id_produit = ?
        ");
        $stmt->execute([
            $_POST['nom'],
            $_POST['categorie'],
            $_POST['fournisseur'],
            $_POST['conditionnement'],
            $_POST['seuil_critique'],
            $_POST['stock_max'],
            $_POST['stock_actuel'],
            $_POST['id_produit']
        ]);
        header('Location: produits.php');
        exit;
    }
}

// ==========================
// Si un produit est sélectionné pour édition (via ?edit=ID), on récupère ses infos pour pré-remplir la modale
// ==========================
$produit_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM Produits WHERE id_produit = ?");
    $stmt->execute([$_GET['edit']]);
    $produit_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Produits</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Polices personnalisées : Montserrat pour les entêtes, Inknut Antiqua pour le tableau -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Inknut+Antiqua:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        /* Logo du site */
        .logo { font-size: 2.5rem; font-weight: bold; border: 3px solid #000; display: inline-block; padding: 0 10px; letter-spacing: 2px; font-family: 'Times New Roman', serif; background: #fff; }
        /* Lien actif dans la navbar */
        .navbar-nav .nav-link.active { background: #fff; border-radius: 5px; color: #000 !important; }
        /* Style des entêtes du tableau */
        .table thead th {
            background: #d3d3d3 !important; /* gris clair */
            color: #23272f !important;
            font-size: 1.15rem;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            /* border-bottom: 3px solid #0d6efd;  Ligne bleue supprimée */
            text-shadow: 1px 1px 2px #0002;
        }
        /* Style des cellules du tableau */
        .table tbody td {
            background: #fff !important;
            color: #23272f;
            vertical-align: middle;
            font-family: 'Inknut Antiqua', serif;
            font-size: 1.08rem;
            font-style: normal;
            letter-spacing: 1px;
        }
        /* Badges pour l'état et le stock */
        .etat-badge, .stock-badge { display: inline-flex; align-items: center; border-radius: 16px; font-size: 1rem; font-weight: bold; padding: 6px 18px; gap: 7px; min-width: 40px; justify-content: center; }
        .stock-badge { background: #e6f4ea; color: #219653; border: 1.5px solid #b7e4c7; }
        .etat-badge.vert { background: #e6f4ea; color: #219653; border: 1.5px solid #b7e4c7; }
        .etat-badge.orange { background: #fff4e0; color: #f2994a; border: 1.5px solid #ffe0b2; }
        .etat-badge.rouge { background: #fdeaea; color: #eb5757; border: 1.5px solid #f8bcbc; }
        .etat-dot { display: inline-block; width: 18px; height: 18px; border-radius: 50%; margin-right: 2px; }
        .etat-dot.vert { background: #219653; }
        .etat-dot.orange { background: #f2994a; }
        .etat-dot.rouge { background: #eb5757; }
        /* Modale Bootstrap */
        .modal-header { border-bottom: none; }
        .modal-content { border-radius: 18px; }
        /* Entêtes cliquables pour le filtre */
        .th-clickable {
            cursor: pointer;
            transition: color 0.2s;
        }
        .th-clickable:hover {
            color: #0d6efd;
            text-decoration: underline;
        }
        /* Selects des filtres */
        .th-filtrable select {
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }
        /* Croix de réinitialisation du filtre */
        .th-filtrable .reset-x {
            font-size: 1.3em;
            color: #111;
            text-decoration: none;
            margin-left: 8px;
            font-weight: bold;
            transition: color 0.2s;
            vertical-align: middle;
        }
        .th-filtrable .reset-x:hover {
            color: #dc3545;
        }
        /* Responsive */
        @media (max-width: 767px) {
            .logo { font-size: 1.5rem; }
            .table-responsive { font-size: 0.95rem; }
            .btn-ajouter { font-size: 1rem; padding: 10px 18px; }
        }
    </style>
</head>
<body>
    <!-- ==========================
         Barre de navigation Bootstrap
         ========================== -->
    <nav class="navbar navbar-expand-lg bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <span class="logo me-4">INVADER</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="produits.php">produits</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php">categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="fournisseurs.php">fournisseurs</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventaires.php">inventaires</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container">
        <!-- ==========================
             En-tête de la page et bouton d'ajout
             ========================== -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="fw-bold mb-0">Produits</h2>
            <a href="produits.php?add=1" class="btn btn-dark btn-ajouter">+ ajouter un produit</a>
        </div>
        <!-- ==========================
             Tableau des produits
             ========================== -->
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <!-- Entête Nom du produit avec filtre -->
                        <th class="th-filtrable">
                            <?php if ($filtre_colonne === 'Nom'): ?>
                                <form method="get" class="d-inline-flex align-items-center gap-1" style="margin-bottom:0;">
                                    <input type="hidden" name="filtre_colonne" value="Nom">
                                    <select name="filtre_valeur" class="form-select form-select-sm" style="width:auto; min-width:120px;" onchange="this.form.submit()">
                                        <option value="">Tous les produits</option>
                                        <?php foreach ($noms_produits as $nom): ?>
                                            <option value="<?= htmlspecialchars($nom) ?>" <?= $filtre_valeur === $nom ? 'selected' : '' ?>><?= htmlspecialchars($nom) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($filtre_valeur): ?>
                                        <a href="produits.php" class="reset-x" title="Réinitialiser">&#10006;</a>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="th-clickable" onclick="window.location.href='?filtre_colonne=Nom'">
                                    nom du produit
                                </span>
                            <?php endif; ?>
                        </th>
                        <!-- Entête Catégorie avec filtre -->
                        <th class="th-filtrable">
                            <?php if ($filtre_colonne === 'categorie'): ?>
                                <form method="get" class="d-inline-flex align-items-center gap-1" style="margin-bottom:0;">
                                    <input type="hidden" name="filtre_colonne" value="categorie">
                                    <select name="filtre_valeur" class="form-select form-select-sm" style="width:auto; min-width:120px;" onchange="this.form.submit()">
                                        <option value="">Toutes les catégories</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= $c['id_categorie'] ?>" <?= $filtre_valeur == $c['id_categorie'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom_categorie']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($filtre_valeur): ?>
                                        <a href="produits.php" class="reset-x" title="Réinitialiser">&#10006;</a>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="th-clickable" onclick="window.location.href='?filtre_colonne=categorie'">
                                    catégories
                                </span>
                            <?php endif; ?>
                        </th>
                        <!-- Entête Stock actuel (pas de filtre) -->
                        <th>stock actuel</th>
                        <!-- Entête État avec filtre -->
                        <th class="th-filtrable">
                            <?php if ($filtre_colonne === 'etat'): ?>
                                <form method="get" class="d-inline-flex align-items-center gap-1" style="margin-bottom:0;">
                                    <input type="hidden" name="filtre_colonne" value="etat">
                                    <select name="filtre_valeur" class="form-select form-select-sm" style="width:auto; min-width:120px;" onchange="this.form.submit()">
                                        <option value="">Tous les états</option>
                                        <?php foreach ($etats as $etat): ?>
                                            <option value="<?= $etat ?>" <?= $filtre_valeur === $etat ? 'selected' : '' ?>><?= ucfirst($etat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($filtre_valeur): ?>
                                        <a href="produits.php" class="reset-x" title="Réinitialiser">&#10006;</a>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="th-clickable" onclick="window.location.href='?filtre_colonne=etat'">
                                    état
                                </span>
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Affichage des lignes du tableau -->
                    <?php foreach ($produits as $produit): ?>
                        <tr style="cursor:pointer" onclick="window.location.href='produits.php?edit=<?= $produit['id_produit'] ?>'">
                            <td><?= htmlspecialchars($produit['Nom']) ?></td>
                            <td><?= htmlspecialchars($produit['nom_categorie']) ?></td>
                            <td>
                                <span class="stock-badge">
                                    <!-- Icône check pour le stock -->
                                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;">
                                        <circle cx="10" cy="10" r="9" stroke="#219653" stroke-width="2" fill="none"/>
                                        <path d="M6 10.5L9 13.5L14 7.5" stroke="#219653" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?= htmlspecialchars($produit['stock_actuel'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <span class="etat-badge <?= $produit['etat'] === 'satisfaisant' ? 'vert' : ($produit['etat'] === 'intermediaire' ? 'orange' : 'rouge') ?>">
                                    <span class="etat-dot <?= $produit['etat'] === 'satisfaisant' ? 'vert' : ($produit['etat'] === 'intermediaire' ? 'orange' : 'rouge') ?>"></span>
                                    <?= htmlspecialchars($produit['etat']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ==========================
         Modale d'ajout de produit (affichée si ?add=1 dans l'URL)
         ========================== -->
    <?php if (isset($_GET['add'])): ?>
    <div class="modal fade show" style="display:block; background:rgba(0,0,0,0.35);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form class="modal-ajout" method="POST" autocomplete="off">
                    <div class="modal-header">
                        <h2 class="modal-title w-100 text-center">ajouter un produit</h2>
                        <a href="produits.php" class="btn-close"></a>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($erreur_ajout)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erreur_ajout) ?></div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <label for="nom" class="form-label">nom du produit</label>
                            <input type="text" id="nom" name="nom" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="categorie" class="form-label">categorie</label>
                            <select id="categorie" name="categorie" class="form-select" required>
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= $categorie['id_categorie'] ?>"><?= htmlspecialchars($categorie['nom_categorie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="fournisseur" class="form-label">fournisseur</label>
                            <select id="fournisseur" name="fournisseur" class="form-select" required>
                                <option value="">-- Sélectionnez un fournisseur --</option>
                                <?php foreach ($fournisseurs as $fournisseur): ?>
                                    <option value="<?= $fournisseur['Idf'] ?>"><?= htmlspecialchars($fournisseur['fournisseur']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="conditionnement" class="form-label">conditionnement</label>
                            <input type="text" id="conditionnement" name="conditionnement" class="form-control" required>
                        </div>
                        <div class="row mb-2">
                            <div class="col">
                                <label for="seuil_critique" class="form-label">seuil de stock critique</label>
                                <input type="number" id="seuil_critique" name="seuil_critique" class="form-control" required>
                            </div>
                            <div class="col">
                                <label for="stock_max" class="form-label">stock maximum</label>
                                <input type="number" id="stock_max" name="stock_max" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="stock_initial" class="form-label">stock initial</label>
                            <input type="number" id="stock_initial" name="stock_initial" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="ajouter_produit" class="btn btn-dark w-100">ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ==========================
         Modale d'édition de produit (affichée si ?edit=ID dans l'URL)
         ========================== -->
    <?php if ($produit_edit): ?>
        <div class="modal fade show" style="display:block; background:rgba(0,0,0,0.35);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h2 class="modal-title w-100 text-center">Modifier le produit</h2>
                            <a href="produits.php" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_produit" value="<?= $produit_edit['id_produit'] ?>">
                            <div class="mb-2">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($produit_edit['Nom']) ?>" required>
                            </div>
                            <div class="mb-2">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <select id="categorie" name="categorie" class="form-select" required>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?= $categorie['id_categorie'] ?>" <?= $categorie['id_categorie'] == $produit_edit['id_categorie'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categorie['nom_categorie']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="fournisseur" class="form-label">Fournisseur</label>
                                <select id="fournisseur" name="fournisseur" class="form-select" required>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?= $fournisseur['Idf'] ?>" <?= $fournisseur['Idf'] == $produit_edit['Idf'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($fournisseur['fournisseur']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="conditionnement" class="form-label">Conditionnement</label>
                                <input type="text" id="conditionnement" name="conditionnement" class="form-control" value="<?= htmlspecialchars($produit_edit['conditionnement'] ?? '') ?>" required>
                            </div>
                            <div class="row mb-2">
                                <div class="col">
                                    <label for="seuil_critique" class="form-label">Seuil de stock critique</label>
                                    <input type="number" id="seuil_critique" name="seuil_critique" class="form-control" value="<?= htmlspecialchars($produit_edit['seuil_critique']) ?>" required>
                                </div>
                                <div class="col">
                                    <label for="stock_max" class="form-label">Stock maximum</label>
                                    <input type="number" id="stock_max" name="stock_max" class="form-control" value="<?= htmlspecialchars($produit_edit['stock_max']) ?>" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="stock_actuel" class="form-label">Stock actuel</label>
                                <input type="number" id="stock_actuel" name="stock_actuel" class="form-control" value="<?= htmlspecialchars($produit_edit['stock_actuel']) ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer d-flex gap-2">
                            <button type="submit" class="btn btn-dark flex-fill">Modifier</button>
                            <button type="submit" name="supprimer" class="btn btn-danger flex-fill">Supprimer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
