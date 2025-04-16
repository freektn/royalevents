<?php
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart data from session or initialize empty cart
$cartItems = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : [];
$cartTotal = isset($_SESSION['cart_total']) ? $_SESSION['cart_total'] : 0;

// Handle form submission
$formSubmitted = false;
$formErrors = [];
$bookingData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form fields
    if (empty($_POST['fullname'])) {
        $formErrors['fullname'] = 'Le nom et prénom sont requis';
    }

    if (empty($_POST['phone'])) {
        $formErrors['phone'] = 'Le numéro de téléphone est requis';
    } elseif (!preg_match('/^[0-9]{8}$/', $_POST['phone'])) {
        $formErrors['phone'] = 'Le numéro de téléphone doit contenir 8 chiffres';
    }

    if (empty($_POST['event_date'])) {
        $formErrors['event_date'] = 'La date est requise';
    } elseif (strtotime($_POST['event_date']) < strtotime('today')) {
        $formErrors['event_date'] = 'La date doit être dans le futur';
    }

    if (empty($_POST['event_type'])) {
        $formErrors['event_type'] = 'Le type d\'événement est requis';
    }

    if (empty($_POST['location'])) {
        $formErrors['location'] = 'La localisation est requise';
    }

    // If no errors and cart is not empty, process the form
    if (empty($formErrors) && !empty($cartItems)) {
        // Prepare booking data
        $bookingData = [
            'fullname' => $_POST['fullname'],
            'phone' => $_POST['phone'],
            'event_date' => $_POST['event_date'],
            'event_type' => $_POST['event_type'],
            'location' => $_POST['location'],
            'notes' => $_POST['notes'] ?? '',
            'total_amount' => $cartTotal
        ];

        // Save booking to database
        $result = saveBooking($bookingData, $cartItems);

        if ($result) {
            $formSubmitted = true;
            $bookingData['reference'] = $result['reference'];

            // Clear the cart after successful booking
            $_SESSION['cart_items'] = [];
            $_SESSION['cart_total'] = 0;
            $cartItems = [];
            $cartTotal = 0;
        } else {
            // Database error
            $formErrors['general'] = 'Une erreur est survenue lors de l\'enregistrement de votre réservation. Veuillez réessayer.';
        }
    } elseif (empty($cartItems)) {
        $formErrors['general'] = 'Votre panier est vide. Veuillez sélectionner des équipements avant de réserver.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réservation - Royal Events</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="booknow.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
  <header>
    <nav class="main-nav">
      <h1 class="brand-title">Royal Events</h1>
      <div class="nav-links">
        <a href="index.php" class="nav-link">Accueil</a>
        <a href="index.php#equipment" class="nav-link">Équipement</a>
        <a href="index.php#contact" class="nav-link">Contact</a>
      </div>
    </nav>
  </header>

  <main class="booking-container">
    <h2 class="booking-title">Réservation</h2>

    <?php if ($formSubmitted): ?>
      <div class="booking-success">
        <h3>Réservation Confirmée!</h3>
        <p>Merci pour votre réservation. Votre numéro de référence est: <strong><?php echo $bookingData['reference']; ?></strong></p>
        <p>Nous vous contacterons bientôt pour confirmer les détails.</p>
        <a href="index.php" class="return-home-btn">Retour à l'accueil</a>
      </div>
    <?php else: ?>

      <div class="booking-content">
        <section class="selected-items">
          <h3>Articles Sélectionnés</h3>

          <?php if (empty($cartItems)): ?>
            <p class="empty-cart-message">Votre panier est vide. Veuillez <a href="index.php#equipment">sélectionner des équipements</a> avant de réserver.</p>
          <?php else: ?>
            <div class="cart-items-list">
              <?php foreach ($cartItems as $item): ?>
                <div class="cart-item-row">
                  <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-thumbnail">
                  <div class="cart-item-info">
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <p class="item-quantity">Quantité: <?php echo $item['quantity']; ?></p>
                    <p class="item-price"><?php echo number_format($item['price'], 0, ',', ' '); ?> TND</p>
                  </div>
                  <p class="item-subtotal"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> TND</p>
                </div>
              <?php endforeach; ?>

              <div class="cart-total-row">
                <span>Total:</span>
                <span class="cart-total-amount"><?php echo number_format($cartTotal, 0, ',', ' '); ?> TND</span>
              </div>
            </div>
          <?php endif; ?>
        </section>

        <section class="booking-form-section">
          <h3>Informations de Réservation</h3>

          <?php if (isset($formErrors['general'])): ?>
            <div class="general-error">
              <?php echo $formErrors['general']; ?>
            </div>
          <?php endif; ?>

          <form method="post" action="booknow.php" class="booking-form" id="bookingForm">
            <div class="form-group">
              <label for="fullname">Nom et Prénom *</label>
              <input type="text" id="fullname" name="fullname" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
              <?php if (isset($formErrors['fullname'])): ?>
                <span class="error-message"><?php echo $formErrors['fullname']; ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="phone">Numéro de Téléphone *</label>
              <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="12345678" required>
              <?php if (isset($formErrors['phone'])): ?>
                <span class="error-message"><?php echo $formErrors['phone']; ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="event_date">Date de l'Événement *</label>
              <input type="date" id="event_date" name="event_date" value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>" required>
              <?php if (isset($formErrors['event_date'])): ?>
                <span class="error-message"><?php echo $formErrors['event_date']; ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="event_type">Type d'Événement *</label>
              <select id="event_type" name="event_type" required>
                <option value="" disabled <?php echo !isset($_POST['event_type']) ? 'selected' : ''; ?>>Sélectionnez un type</option>
                <option value="mariage" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'mariage') ? 'selected' : ''; ?>>Mariage</option>
                <option value="ceremonie" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'ceremonie') ? 'selected' : ''; ?>>Cérémonie</option>
                <option value="reception" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'reception') ? 'selected' : ''; ?>>Réception</option>
                <option value="soiree" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'soiree') ? 'selected' : ''; ?>>Soirée</option>
                <option value="conference" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'conference') ? 'selected' : ''; ?>>Conférence</option>
                <option value="autre" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] === 'autre') ? 'selected' : ''; ?>>Autre</option>
              </select>
              <?php if (isset($formErrors['event_type'])): ?>
                <span class="error-message"><?php echo $formErrors['event_type']; ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="location">Localisation (Tunisie) *</label>
              <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" placeholder="Ville, Adresse" required>
              <?php if (isset($formErrors['location'])): ?>
                <span class="error-message"><?php echo $formErrors['location']; ?></span>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="notes">Notes Supplémentaires</label>
              <textarea id="notes" name="notes" rows="4"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
              <a href="index.php" class="cancel-btn">Annuler</a>
              <button type="submit" class="submit-btn" <?php echo empty($cartItems) ? 'disabled' : ''; ?>>Confirmer la Réservation</button>
            </div>
          </form>
        </section>
      </div>
    <?php endif; ?>
  </main>

  <footer class="booking-footer">
    <p>&copy; <?php echo date('Y'); ?> Royal Events. Tous droits réservés.</p>
  </footer>

  <script src="booknow.js"></script>
</body>
</html>