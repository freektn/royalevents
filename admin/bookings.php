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
    header('Location: bookings.php');
    exit;
}

// Get bookings if logged in
$bookings = [];
if ($isLoggedIn) {
    // Get all bookings
    $sql = "SELECT * FROM bookings ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }

    // Get booking stats
    $stats = [
        'total' => count($bookings),
        'pending' => 0,
        'confirmed' => 0,
        'cancelled' => 0,
        'total_revenue' => 0
    ];

    foreach ($bookings as $booking) {
        $stats[$booking['status']]++;
        if ($booking['status'] !== 'cancelled') {
            $stats['total_revenue'] += $booking['total_amount'];
        }
    }
}

// Get booking details if viewing a specific booking
$bookingDetails = null;
if ($isLoggedIn && isset($_GET['id'])) {
    $bookingDetails = getBookingById($_GET['id']);
}

// Handle status update
if ($isLoggedIn && isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['booking_id']) && isset($_POST['status'])) {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['status'];

    // Update booking status
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $bookingId);

    if ($stmt->execute()) {
        // Refresh booking details
        $bookingDetails = getBookingById($bookingId);
        $statusUpdateSuccess = true;
    } else {
        $statusUpdateError = "Erreur lors de la mise à jour du statut";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Royal Events</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1c1c1c;
            --secondary-color: #f5f5f5;
            --accent-color: #e0a800;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #fd7e14;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
            line-height: 1.6;
        }

        /* Login Page */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--gray-100);
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .login-body {
            padding: 30px;
        }

        .login-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(28, 28, 28, 0.1);
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 12px 15px;
            font-size: 15px;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-primary {
            color: white;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #000;
            border-color: #000;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            font-size: 22px;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: var(--transition);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .user-name {
            font-weight: 500;
        }

        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            min-width: 180px;
            z-index: 1000;
            margin-top: 10px;
            display: none;
        }

        .user-dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 10px 15px;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--gray-100);
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .stat-icon.blue {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        .stat-icon.green {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .stat-icon.orange {
            background-color: rgba(253, 126, 20, 0.1);
            color: var(--warning-color);
        }

        .stat-icon.red {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 14px;
        }

        /* Bookings Table */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .table th {
            font-weight: 600;
            color: var(--gray-700);
            background-color: var(--gray-100);
        }

        .table tr:hover {
            background-color: var(--gray-50);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 30px;
        }

        .badge-pending {
            background-color: rgba(253, 126, 20, 0.1);
            color: var(--warning-color);
        }

        .badge-confirmed {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .badge-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }

        /* Booking Details */
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .booking-reference {
            font-size: 14px;
            color: var(--gray-600);
        }

        .booking-status {
            display: flex;
            align-items: center;
        }

        .status-select {
            margin-left: 10px;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
        }

        .booking-notes {
            background-color: var(--gray-100);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
        }

        .booking-items {
            margin-top: 30px;
        }

        .item-row {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--gray-200);
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-right: 15px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .item-meta {
            display: flex;
            font-size: 13px;
            color: var(--gray-600);
        }

        .item-price, .item-quantity {
            margin-right: 15px;
        }

        .item-subtotal {
            font-weight: 500;
            color: var(--primary-color);
        }

        .booking-total {
            display: flex;
            justify-content: flex-end;
            padding: 15px;
            font-weight: 600;
            font-size: 18px;
        }

        .booking-total-label {
            margin-right: 15px;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--gray-700);
            text-decoration: none;
            margin-bottom: 20px;
        }

        .back-link i {
            margin-right: 5px;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state-icon {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--gray-700);
        }

        .empty-state-description {
            color: var(--gray-600);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-brand, .menu-text {
                display: none;
            }

            .sidebar-header {
                padding: 15px;
            }

            .menu-item {
                justify-content: center;
                padding: 15px;
            }

            .menu-item i {
                margin-right: 0;
                font-size: 20px;
            }

            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .booking-info {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-dropdown {
                margin-top: 15px;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <!-- Login Page -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Royal Events Admin</h1>
                </div>
                <div class="login-body">
                    <?php if (isset($loginError)): ?>
                        <div class="login-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $loginError; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="bookings.php">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label for="username">Nom d'utilisateur</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Dashboard -->
        <div class="dashboard">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <a href="#" class="sidebar-brand">Royal Events</a>
                </div>
                <div class="sidebar-menu">
                    <a href="bookings.php" class="menu-item active">
                        <i class="fas fa-calendar-check"></i>
                        <span class="menu-text">Réservations</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span class="menu-text">Produits</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Catégories</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Clients</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fas fa-cog"></i>
                        <span class="menu-text">Paramètres</span>
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Topbar -->
                <div class="topbar">
                    <h1 class="page-title">
                        <?php echo $bookingDetails ? 'Détails de la Réservation' : 'Gestion des Réservations'; ?>
                    </h1>
                    <div class="user-dropdown">
                        <div class="user-dropdown-toggle" id="userDropdown">
                            <div class="user-avatar">A</div>
                            <div class="user-name">Admin</div>
                        </div>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                            <a href="?action=logout" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($bookingDetails): ?>
                    <!-- Booking Details View -->
                    <a href="bookings.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Retour aux réservations
                    </a>

                    <?php if (isset($statusUpdateSuccess)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Le statut de la réservation a été mis à jour avec succès.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($statusUpdateError)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $statusUpdateError; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Réservation #<?php echo htmlspecialchars($bookingDetails['booking_reference']); ?></h2>
                                <div class="booking-reference">
                                    Créée le <?php echo date('d/m/Y à H:i', strtotime($bookingDetails['created_at'])); ?>
                                </div>
                            </div>
                            <div class="booking-status">
                                <form method="post" action="bookings.php?id=<?php echo $bookingDetails['id']; ?>" id="statusForm">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?php echo $bookingDetails['id']; ?>">
                                    <div class="status-select">
                                        <select name="status" class="form-control" id="statusSelect" onchange="document.getElementById('statusForm').submit()">
                                            <option value="pending" <?php echo $bookingDetails['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                            <option value="confirmed" <?php echo $bookingDetails['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                                            <option value="cancelled" <?php echo $bookingDetails['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="booking-info">
                                <div>
                                    <div class="info-group">
                                        <div class="info-label">Client</div>
                                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['fullname']); ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Téléphone</div>
                                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['phone']); ?></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="info-group">
                                        <div class="info-label">Date de l'événement</div>
                                        <div class="info-value"><?php echo date('d/m/Y', strtotime($bookingDetails['event_date'])); ?></div>
                                    </div>
                                    <div class="info-group">
                                        <div class="info-label">Type d'événement</div>
                                        <div class="info-value"><?php echo ucfirst(htmlspecialchars($bookingDetails['event_type'])); ?></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="info-group">
                                        <div class="info-label">Localisation</div>
                                        <div class="info-value"><?php echo htmlspecialchars($bookingDetails['location']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($bookingDetails['notes'])): ?>
                                <div class="booking-notes">
                                    <div class="info-label">Notes</div>
                                    <div><?php echo nl2br(htmlspecialchars($bookingDetails['notes'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <h3 class="card-title" style="margin-bottom: 15px;">Articles réservés</h3>

                            <div class="booking-items">
                                <?php foreach ($bookingDetails['items'] as $item): ?>
                                    <div class="item-row">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">

                                        <div class="item-details">
                                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="item-meta">
                                                <div class="item-price"><?php echo number_format($item['price'], 0, ',', ' '); ?> TND</div>
                                                <div class="item-quantity">Quantité: <?php echo $item['quantity']; ?></div>
                                            </div>
                                        </div>

                                        <div class="item-subtotal"><?php echo number_format($item['subtotal'], 0, ',', ' '); ?> TND</div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="booking-total">
                                    <div class="booking-total-label">Total:</div>
                                    <div class="booking-total-value"><?php echo number_format($bookingDetails['total_amount'], 0, ',', ' '); ?> TND</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Bookings List View -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Réservations totales</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                                <div class="stat-label">En attente</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
                                <div class="stat-label">Confirmées</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
                                <div class="stat-label">Annulées</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Liste des réservations</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($bookings)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>
                                    <h3 class="empty-state-title">Aucune réservation trouvée</h3>
                                    <p class="empty-state-description">
                                        Il n'y a pas encore de réservations dans le système.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Référence</th>
                                                <th>Client</th>
                                                <th>Date d'événement</th>
                                                <th>Type</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Date de création</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($booking['event_date'])); ?></td>
                                                    <td><?php echo ucfirst(htmlspecialchars($booking['event_type'])); ?></td>
                                                    <td><?php echo number_format($booking['total_amount'], 0, ',', ' '); ?> TND</td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $booking['status']; ?>">
                                                            <?php
                                                                switch($booking['status']) {
                                                                    case 'pending': echo 'En attente'; break;
                                                                    case 'confirmed': echo 'Confirmée'; break;
                                                                    case 'cancelled': echo 'Annulée'; break;
                                                                    default: echo $booking['status'];
                                                                }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                                                    <td>
                                                        <a href="?id=<?php echo $booking['id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> Voir
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // User dropdown toggle
            document.getElementById('userDropdown').addEventListener('click', function() {
                document.getElementById('userDropdownMenu').classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.user-dropdown')) {
                    const dropdown = document.getElementById('userDropdownMenu');
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>