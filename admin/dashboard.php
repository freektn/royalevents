<?php
require_once 'includes/header.php';

// Get dashboard statistics
$stats = [
    'total_bookings' => 0,
    'pending_bookings' => 0,
    'total_products' => 0,
    'total_revenue' => 0,
    'recent_bookings' => [],
    'popular_products' => []
];

// Get total bookings
$sql = "SELECT COUNT(*) as count FROM bookings";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_bookings'] = $row['count'];
}

// Get pending bookings
$sql = "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['pending_bookings'] = $row['count'];
}

// Get total products
$sql = "SELECT COUNT(*) as count FROM products";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_products'] = $row['count'];
}

// Get total revenue
$sql = "SELECT SUM(total_amount) as total FROM bookings WHERE status != 'cancelled'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_revenue'] = $row['total'] ?? 0;
}

// Get recent bookings
$sql = "SELECT b.id, b.booking_reference, b.fullname, b.event_date, b.total_amount, b.status, b.created_at
        FROM bookings b
        ORDER BY b.created_at DESC
        LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['recent_bookings'][] = $row;
    }
}

// Get popular products
$sql = "SELECT p.id, p.name, COUNT(bi.id) as booking_count, SUM(bi.quantity) as total_quantity
        FROM products p
        JOIN booking_items bi ON p.id = bi.product_id
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 5";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stats['popular_products'][] = $row;
    }
}
?>

<div class="dashboard-header">
    <h1>Tableau de bord</h1>
    <p>Bienvenue, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Réservations</h3>
            <div class="card-icon blue">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
        <div class="card-body">
            <div class="card-value"><?php echo $stats['total_bookings']; ?></div>
            <div class="card-label">Total des réservations</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">En attente</h3>
            <div class="card-icon orange">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        <div class="card-body">
            <div class="card-value"><?php echo $stats['pending_bookings']; ?></div>
            <div class="card-label">Réservations en attente</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Produits</h3>
            <div class="card-icon green">
                <i class="fas fa-box"></i>
            </div>
        </div>
        <div class="card-body">
            <div class="card-value"><?php echo $stats['total_products']; ?></div>
            <div class="card-label">Total des produits</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Revenus</h3>
            <div class="card-icon red">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
        <div class="card-body">
            <div class="card-value"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> TND</div>
            <div class="card-label">Total des revenus</div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Réservations récentes</h3>
        <a href="bookings.php" class="btn btn-primary btn-sm">Voir tout</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Référence</th>
                <th>Client</th>
                <th>Date d'événement</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date de réservation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stats['recent_bookings'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Aucune réservation trouvée</td>
                </tr>
            <?php else: ?>
                <?php foreach ($stats['recent_bookings'] as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                        <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['event_date'])); ?></td>
                        <td><?php echo number_format($booking['total_amount'], 0, ',', ' '); ?> TND</td>
                        <td>
                            <?php
                                $statusClasses = [
                                    'pending' => 'badge-pending',
                                    'confirmed' => 'badge-confirmed',
                                    'cancelled' => 'badge-cancelled'
                                ];
                                $statusLabels = [
                                    'pending' => 'En attente',
                                    'confirmed' => 'Confirmé',
                                    'cancelled' => 'Annulé'
                                ];
                            ?>
                            <span class="badge <?php echo $statusClasses[$booking['status']]; ?>">
                                <?php echo $statusLabels[$booking['status']]; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                        <td>
                            <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-sm">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Popular Products -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Produits populaires</h3>
        <a href="products.php" class="btn btn-primary btn-sm">Voir tout</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Nombre de réservations</th>
                <th>Quantité totale</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($stats['popular_products'])): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Aucun produit trouvé</td>
                </tr>
            <?php else: ?>
                <?php foreach ($stats['popular_products'] as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo $product['booking_count']; ?></td>
                        <td><?php echo $product['total_quantity']; ?></td>
                        <td>
                            <a href="products.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>