<?php
require_once '../functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple admin authentication (in a real app, use proper authentication)
$adminUsername = "admin";
$adminPassword = "admin123"; // In a real app, use hashed passwords

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($_POST['username'] === $adminUsername && $_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = "Nom d'utilisateur ou mot de passe incorrect";
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    $isLoggedIn = false;
    header('Location: category.php');
    exit;
}

// Initialize variables
$categories = [];
$message = '';
$messageType = '';
$editCategory = null;

// Get all categories
if ($isLoggedIn) {
    $categories = getAllCategories();

    // Handle category actions if logged in
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add new category
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);

            // Validate inputs
            if (empty($title) || empty($slug)) {
                $message = "Le titre et le slug sont requis.";
                $messageType = "error";
            } else {
                // Handle image upload
                $imagePath = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCategoryImage($_FILES['image']);
                    if ($uploadResult['success']) {
                        $imagePath = $uploadResult['path'];
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = "error";
                    }
                } else {
                    $message = "L'image est requise.";
                    $messageType = "error";
                }

                // Add category if image upload was successful
                if (!empty($imagePath)) {
                    $result = addCategory($title, $slug, $imagePath);
                    if ($result) {
                        $message = "Catégorie ajoutée avec succès.";
                        $messageType = "success";
                        // Refresh categories list
                        $categories = getAllCategories();
                    } else {
                        $message = "Erreur lors de l'ajout de la catégorie.";
                        $messageType = "error";
                    }
                }
            }
        }

        // Update category
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $slug = trim($_POST['slug']);
            $active = isset($_POST['active']) ? 1 : 0;
            $displayOrder = (int)$_POST['display_order'];

            // Validate inputs
            if (empty($title) || empty($slug)) {
                $message = "Le titre et le slug sont requis.";
                $messageType = "error";
            } else {
                // Handle image upload if a new image was provided
                $imagePath = $_POST['current_image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadCategoryImage($_FILES['image']);
                    if ($uploadResult['success']) {
                        $imagePath = $uploadResult['path'];
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = "error";
                    }
                }

                // Update category
                $result = updateCategory($id, $title, $slug, $imagePath, $active, $displayOrder);
                if ($result) {
                    $message = "Catégorie mise à jour avec succès.";
                    $messageType = "success";
                    // Refresh categories list
                    $categories = getAllCategories();
                } else {
                    $message = "Erreur lors de la mise à jour de la catégorie.";
                    $messageType = "error";
                }
            }
        }

        // Delete category
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];

            // Check if category has products
            $hasProducts = categoryHasProducts($id);
            if ($hasProducts) {
                $message = "Impossible de supprimer cette catégorie car elle contient des produits.";
                $messageType = "error";
            } else {
                $result = deleteCategory($id);
                if ($result) {
                    $message = "Catégorie supprimée avec succès.";
                    $messageType = "success";
                    // Refresh categories list
                    $categories = getAllCategories();
                } else {
                    $message = "Erreur lors de la suppression de la catégorie.";
                    $messageType = "error";
                }
            }
        }
    }

    // Load category for editing
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $editCategory = getCategoryById((int)$_GET['edit']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - Royal Events</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #f9f9f9;
            font-family: "Helvetica Now Text Medium", Helvetica, Arial, sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-title {
            font-size: 24px;
            margin: 0;
        }

        .admin-nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-nav a {
            padding: 10px 15px;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }

        .admin-nav a.active {
            background-color: #1c1c1c;
            color: white;
        }

        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .login-form {
            max-width: 400px;
            margin: 100px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-group input[type="file"] {
            padding: 10px 0;
        }

        .form-group .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .login-btn, .submit-btn {
            background-color: #1c1c1c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        .cancel-btn {
            background-color: #f5f5f5;
            color: #333;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-right: 10px;
        }

        .login-error {
            color: #f44336;
            margin-bottom: 20px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .categories-table th, .categories-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .categories-table th {
            background-color: #f5f5f5;
            font-weight: 500;
        }

        .categories-table tr:hover {
            background-color: #f9f9f9;
        }

        .category-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }

        .edit-btn {
            background-color: #2196f3;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
            cursor: pointer;
            border: none;
        }

        .add-btn {
            background-color: #4caf50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 20px;
            display: inline-block;
        }

        .form-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .status-active {
            color: #4caf50;
            font-weight: 500;
        }

        .status-inactive {
            color: #f44336;
            font-weight: 500;
        }

        .no-categories {
            text-align: center;
            padding: 50px;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <h1 class="brand-title">Royal Events</h1>
        <div class="nav-links">
            <a href="../index.php" class="nav-link">Retour au site</a>
        </div>
    </nav>

    <div class="admin-container">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Form -->
            <div class="login-form">
                <h2>Connexion Administration</h2>

                <?php if (isset($loginError)): ?>
                    <div class="login-error"><?php echo $loginError; ?></div>
                <?php endif; ?>

                <form method="post" action="category.php">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="login-btn">Se connecter</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="admin-header">
                <h1 class="admin-title">Administration</h1>
                <a href="?action=logout" class="logout-btn">Déconnexion</a>
            </div>

            <div class="admin-nav">
                <a href="bookings.php">Réservations</a>
                <a href="category.php" class="active">Catégories</a>
                <a href="products.php">Produits</a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($editCategory): ?>
                <!-- Edit Category Form -->
                <div class="form-card">
                    <h2>Modifier la catégorie</h2>

                    <form method="post" action="category.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $editCategory['image_url']; ?>">

                        <div class="form-group">
                            <label for="title">Titre</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editCategory['title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug (identifiant unique)</label>
                            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($editCategory['slug']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <p>Image actuelle:</p>
                            <img src="<?php echo htmlspecialchars($editCategory['image_url']); ?>" alt="<?php echo htmlspecialchars($editCategory['title']); ?>" class="preview-image">
                        </div>

                        <div class="form-group">
                            <label for="display_order">Ordre d'affichage</label>
                            <input type="number" id="display_order" name="display_order" value="<?php echo $editCategory['display_order']; ?>" min="0">
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="active" <?php echo $editCategory['active'] ? 'checked' : ''; ?>>
                                Actif
                            </label>
                        </div>

                        <div class="form-actions">
                            <a href="category.php" class="cancel-btn">Annuler</a>
                            <button type="submit" class="submit-btn">Mettre à jour</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Add Category Form -->
                <div class="form-card">
                    <h2>Ajouter une nouvelle catégorie</h2>

                    <form method="post" action="category.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">

                        <div class="form-group">
                            <label for="title">Titre</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug (identifiant unique)</label>
                            <input type="text" id="slug" name="slug" required>
                        </div>

                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" id="image" name="image" accept="image/*" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="submit-btn">Ajouter</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Categories List -->
            <h2>Liste des catégories</h2>

            <?php if (empty($categories)): ?>
                <div class="no-categories">
                    <p>Aucune catégorie trouvée.</p>
                </div>
            <?php else: ?>
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Slug</th>
                            <th>Ordre</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($category['image_url']); ?>" alt="<?php echo htmlspecialchars($category['title']); ?>" class="category-image">
                                </td>
                                <td><?php echo htmlspecialchars($category['title']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td><?php echo $category['display_order']; ?></td>
                                <td>
                                    <span class="status-<?php echo $category['active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $category['active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $category['id']; ?>" class="action-btn edit-btn">Modifier</a>

                                    <form method="post" action="category.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');

            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.dataset.manuallyEdited) {
                        const slug = titleInput.value
                            .toLowerCase()
                            .replace(/[^\w\s-]/g, '') // Remove special characters
                            .replace(/\s+/g, '-')     // Replace spaces with hyphens
                            .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen

                        slugInput.value = slug;
                    }
                });

                // Mark slug as manually edited when user types in it
                slugInput.addEventListener('input', function() {
                    slugInput.dataset.manuallyEdited = 'true';
                });
            }
        });
    </script>
</body>
</html>