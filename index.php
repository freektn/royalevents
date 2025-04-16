<?php
require_once 'functions.php';

// Get all categories - this will fetch ALL active categories from the database
// The number of categories is not fixed and will display whatever is in the database
$categories = getCategories();

// Get all products grouped by category - this will fetch ALL active products for each category
// The number of products is not fixed and will display all products in each category
$productsByCategory = getAllProductsByCategory();

// Convert products array to JSON for JavaScript
$productsJson = json_encode($productsByCategory);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Royal Events - Premium Event Equipment</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
</head>
<body>
  <header class="main-container">
    <nav class="main-nav">
      <h1 class="brand-title">Royal Events</h1>
      <div class="nav-links">
        <a href="#equipment" class="nav-link">Equipment</a>
        <a href="#services" class="nav-link">Services</a>
        <a href="#contact" class="nav-link">Contact</a>
        <div class="cart-container">
          <button class="cart-icon-btn" aria-label="Shopping Cart">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="cart-icon">
              <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
            </svg>
            <span class="cart-count">0</span>
          </button>
          <div class="cart-dropdown">
            <div class="cart-header">
              <h4>Your Cart</h4>
              <button class="close-cart-btn">&times;</button>
            </div>
            <div class="cart-items">
              <!-- Cart items will be added here dynamically -->
            </div>
            <div class="cart-footer">
              <div class="cart-total">Total: <span>0 TND</span></div>
              <button class="checkout-btn">Book Now</button>
            </div>
          </div>
        </div>
        <a href="booknow.php" class="book-now-btn">Book Now</a>
      </div>
    </nav>
    <section class="hero-section">
      <img src="https://images.pexels.com/photos/19351563/pexels-photo-19351563.jpeg" class="hero-image" alt="Luxury event setup">
      <div class="hero-content">
        <h2 class="hero-title">Elevate Your Celebrations</h2>
        <p class="hero-description">
          Luxury Sound Systems • Premium Furniture • Professional Lighting
          Complete Event Equipment Solutions
        </p>
        <div class="hero-buttons">
          <button class="primary-btn view-equipment-btn">View Equipment</button>
          <button class="secondary-btn quote-btn">Get Quote</button>
        </div>
      </div>
    </section>
    <section id="equipment" class="equipment-section">
      <h3 class="section-title">Our Equipment</h3>
      <div class="category-grid">
        <?php if (empty($categories)): ?>
          <p class="no-data-message">No equipment categories available at the moment.</p>
        <?php else: ?>
          <?php
          // Loop through ALL categories from the database
          // The grid will automatically adjust to display any number of categories
          foreach ($categories as $category):
          ?>
            <article class="category-card" data-category="<?php echo htmlspecialchars($category['slug']); ?>">
              <img src="<?php echo htmlspecialchars($category['image']); ?>" class="category-image" alt="<?php echo htmlspecialchars($category['title']); ?>">
              <div class="category-content">
                <h4 class="category-title"><?php echo htmlspecialchars($category['title']); ?></h4>
                <button class="shop-btn">Shop Now</button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
    <section id="products" class="products-section" style="display: none;">
      <h2 class="products-title"></h2>
      <div class="products-grid">
        <!-- Products will be dynamically inserted here -->
      </div>
    </section>
    <section id="contact" class="cta-section">
      <h3 class="cta-title">Ready to Create Your Perfect Event?</h3>
      <p class="cta-description">Contact us for personalized service and expert advice</p>
      <button class="contact-btn">Get in Touch</button>
    </section>
  </header>

  <script>
    // Pass PHP data to JavaScript
    const productsData = <?php echo $productsJson; ?>;

    // Initialize empty cart
    const cartData = {
      items: [],
      total: 0
    };
  </script>
  <script src="script.js"></script>
</body>
</html>