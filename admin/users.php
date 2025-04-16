<?php
require_once '../functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['admin_user_id']);
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = getAdminUserById($_SESSION['admin_user_id']);
    if (!$currentUser) {
        // User not found in database, log them out
        unset($_SESSION['admin_user_id']);
        $isLoggedIn = false;
    } else if ($currentUser['role'] !== 'admin') {
        // Only admins can access user management
        header('Location: bookings.php');
        exit;
    }
} else {
    // Redirect to login page
    header('Location: bookings.php');
    exit;
}

// Get all users
$users = getAllAdminUsers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Royal Events</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            padding-top: 80px;
            background-color: #f9f9f9;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-title {
            font-size: 28px;
            margin: 0;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-user-name {
            font-weight: 500;
        }

        .admin-user-role {
            background-color: #1c1c1c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .admin-nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-nav-link {
            padding: 10px 20px;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }

        .admin-nav-link.active {
            background-color: #1c1c1c;
            color: white;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background-color: #f5f5f5;
            font-weight: 500;
        }

        .users-table tr:hover {
            background-color: #f9f9f9;
        }

        .role-admin {
            background-color: #1c1c1c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .role-manager {
            background-color: #2196f3;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .role-staff {
            background-color: #4caf50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <h1 class="brand-title">Royal Events</h1>
        <div class="nav-links">
            <a href="../index.php" class="nav-link">Retour au Site</a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title">Gestion des Utilisateurs</h2>
            <div class="admin-user-info">
                <span class="admin-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                <span class="admin-user-role"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                <a href="bookings.php?action=logout" class="logout-btn">Déconnexion</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="bookings.php" class="admin-nav-link">Réservations</a>
            <a href="users.php" class="admin-nav-link active">Utilisateurs</a>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Nom complet</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Dernière connexion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-<?php echo $user['role']; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                                echo $user['last_login']
                                    ? date('d/m/Y H:i', strtotime($user['last_login']))
                                    : 'Jamais connecté';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>