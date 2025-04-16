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
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - Royal Events</title>
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

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .login-btn {
            background-color: #1c1c1c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        .login-error {
            color: #f44336;
            margin-bottom: 20px;
        }

        .coming-soon {
            text-align: center;
            padding: 100px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .coming-soon h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .coming-soon p {
            font-size: 16px;
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

                <form method="post" action="products.php">
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
                <a href="category.php">Catégories</a>
                <a href="products.php" class="active">Produits</a>
            </div>

            <div class="coming-soon">
                <h2>Gestion des Produits</h2>
                <p>Cette fonctionnalité sera disponible prochainement.</p>
                <p>Veuillez d'abord configurer vos catégories.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>