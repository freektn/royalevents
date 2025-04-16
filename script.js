(() => {
  const state = {
    isMenuOpen: false,
    activeCategory: "all",
    products: productsData || {}, // Use data from PHP
    cart: {
      items: [],
      isOpen: false,

      // Add item to cart
      addItem(product, quantity = 1) {
        const existingItem = this.items.find((item) => item.id === product.id);

        if (existingItem) {
          existingItem.quantity += quantity;
        } else {
          this.items.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: quantity,
          });
        }

        this.updateCartUI();
        this.showNotification(`${product.name} added to cart`);
      },

      // Remove item from cart
      removeItem(productId) {
        this.items = this.items.filter((item) => item.id !== productId);
        this.updateCartUI();
      },

      // Update item quantity
      updateQuantity(productId, quantity) {
        const item = this.items.find((item) => item.id === productId);
        if (item) {
          item.quantity = quantity;
          if (item.quantity <= 0) {
            this.removeItem(productId);
          } else {
            this.updateCartUI();
          }
        }
      },

      // Calculate total
      calculateTotal() {
        return this.items.reduce((total, item) => {
          return total + item.price * item.quantity;
        }, 0);
      },

      // Toggle cart dropdown
      toggleCart() {
        this.isOpen = !this.isOpen;
        const cartDropdown = document.querySelector(".cart-dropdown");

        if (this.isOpen) {
          cartDropdown.classList.add("active");
        } else {
          cartDropdown.classList.remove("active");
        }
      },

      // Close cart dropdown
      closeCart() {
        this.isOpen = false;
        document.querySelector(".cart-dropdown").classList.remove("active");
      },

      // Show notification
      showNotification(message) {
        // Create notification element
        const notification = document.createElement("div");
        notification.className = "cart-notification";
        notification.textContent = message;

        // Add to DOM
        document.body.appendChild(notification);

        // Remove after animation
        setTimeout(() => {
          notification.classList.add("show");

          setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => {
              notification.remove();
            }, 300);
          }, 2000);
        }, 10);
      },

      // Update cart UI
      updateCartUI() {
        // Update cart count
        const cartCount = document.querySelector(".cart-count");
        const totalItems = this.items.reduce(
          (count, item) => count + item.quantity,
          0,
        );
        cartCount.textContent = totalItems;

        // Update cart items
        const cartItemsContainer = document.querySelector(".cart-items");
        cartItemsContainer.innerHTML = "";

        if (this.items.length === 0) {
          cartItemsContainer.innerHTML =
            '<p class="empty-cart-message">Your cart is empty</p>';
        } else {
          this.items.forEach((item) => {
            const cartItem = document.createElement("div");
            cartItem.className = "cart-item";
            cartItem.dataset.id = item.id;

            cartItem.innerHTML = `
              <img src="${item.image}" class="cart-item-image" alt="${item.name}">
              <div class="cart-item-details">
                <h5 class="cart-item-title">${item.name}</h5>
                <p class="cart-item-price">${Number(item.price).toLocaleString()} TND</p>
                <div class="cart-item-quantity">
                  <button class="quantity-btn decrease-btn" data-id="${item.id}">-</button>
                  <input type="text" class="quantity-input" value="${item.quantity}" readonly>
                  <button class="quantity-btn increase-btn" data-id="${item.id}">+</button>
                </div>
              </div>
              <button class="remove-item-btn" data-id="${item.id}">&times;</button>
            `;

            cartItemsContainer.appendChild(cartItem);
          });
        }

        // Update total
        const cartTotal = document.querySelector(".cart-total span");
        cartTotal.textContent = `${this.calculateTotal().toLocaleString()} TND`;
      },
    },

    setCategory(category) {
      this.activeCategory = category;
      this.updateUI();
    },

    getProductsByCategory() {
      return this.products[this.activeCategory] || [];
    },

    updateUI() {
      const productsSection = document.getElementById("products");
      const productsTitleElement = document.querySelector(".products-title");
      const productsGridElement = document.querySelector(".products-grid");

      if (this.activeCategory === "all") {
        productsSection.style.display = "none";
        return;
      }

      // Update title
      const formattedTitle = this.activeCategory
        .split("-")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");

      productsTitleElement.textContent = formattedTitle;

      // Clear existing products
      productsGridElement.innerHTML = "";

      // Get products for the selected category
      // This will handle ANY number of products - the grid will adjust automatically
      const products = this.getProductsByCategory();

      if (!products || products.length === 0) {
        productsGridElement.innerHTML =
          '<p class="no-products-message">No products available in this category.</p>';
      } else {
        // Loop through ALL products in the category
        // The grid layout will automatically adjust to display any number of products
        products.forEach((product) => {
          const productCard = document.createElement("article");
          productCard.className = "product-card";
          productCard.dataset.id = product.id;

          productCard.innerHTML = `
            <img src="${product.image}" class="product-image" alt="${product.name}">
            <div class="product-content">
              <h3 class="product-title">${product.name}</h3>
              <p class="product-price">
                <span>${Number(product.price).toLocaleString()}</span>
                <span>TND</span>
              </p>
              <button class="add-to-cart-btn" data-id="${product.id}">Add to Cart</button>
            </div>
          `;

          productsGridElement.appendChild(productCard);
        });
      }

      // Show products section
      productsSection.style.display = "block";

      // Scroll to products section
      productsSection.scrollIntoView({ behavior: "smooth" });
    },

    // Find product by ID across all categories
    findProductById(productId) {
      for (const category in this.products) {
        const product = this.products[category].find((p) => p.id == productId);
        if (product) return product;
      }
      return null;
    },
  };

  // Initialize event listeners
  function initEventListeners() {
    // Category card click events
    document.querySelectorAll(".category-card").forEach((card) => {
      card.addEventListener("click", () => {
        const category = card.dataset.category;
        state.setCategory(category);
      });
    });

    // View Equipment button
    const viewEquipmentBtn = document.querySelector(".view-equipment-btn");
    if (viewEquipmentBtn) {
      viewEquipmentBtn.addEventListener("click", () => {
        // Get the first category from the available categories
        const firstCategoryCard = document.querySelector(".category-card");
        if (firstCategoryCard) {
          const firstCategory = firstCategoryCard.dataset.category;
          state.setCategory(firstCategory);
        }
      });
    }

    // Add to cart buttons (delegated)
    document.addEventListener("click", (event) => {
      // Add to cart button
      if (event.target.classList.contains("add-to-cart-btn")) {
        const productId = event.target.dataset.id;
        const product = state.findProductById(productId);

        if (product) {
          state.cart.addItem(product);
        }
      }

      // Cart icon click
      if (event.target.closest(".cart-icon-btn")) {
        state.cart.toggleCart();
      }

      // Close cart button
      if (event.target.classList.contains("close-cart-btn")) {
        state.cart.closeCart();
      }

      // Increase quantity button
      if (event.target.classList.contains("increase-btn")) {
        event.stopPropagation(); // Prevent event from bubbling up to document
        const productId = parseInt(event.target.dataset.id);
        const item = state.cart.items.find((item) => item.id === productId);
        if (item) {
          state.cart.updateQuantity(productId, item.quantity + 1);
        }
      }

      // Decrease quantity button
      if (event.target.classList.contains("decrease-btn")) {
        event.stopPropagation(); // Prevent event from bubbling up to document
        const productId = parseInt(event.target.dataset.id);
        const item = state.cart.items.find((item) => item.id === productId);
        if (item && item.quantity > 1) {
          state.cart.updateQuantity(productId, item.quantity - 1);
        }
      }

      // Remove item button
      if (event.target.classList.contains("remove-item-btn")) {
        event.stopPropagation(); // Prevent event from bubbling up to document
        const productId = parseInt(event.target.dataset.id);
        state.cart.removeItem(productId);
      }

      // Book Now button (formerly checkout)
      if (event.target.classList.contains("checkout-btn")) {
        if (state.cart.items.length > 0) {
          // Save cart data to session storage before redirecting
          saveCartToSession();
          // Redirect to booking page
          window.location.href = "booknow.php";
        } else {
          alert("Your cart is empty");
        }
      }
    });

    // Close cart when clicking outside
    document.addEventListener("click", (event) => {
      const cartContainer = document.querySelector(".cart-container");
      const cartDropdown = document.querySelector(".cart-dropdown");

      // Check if the click was on a cart-related element
      const isCartElement =
        event.target.closest(".cart-item-quantity") ||
        event.target.closest(".remove-item-btn");

      if (
        state.cart.isOpen &&
        !cartContainer.contains(event.target) &&
        !cartDropdown.contains(event.target) &&
        !isCartElement
      ) {
        state.cart.closeCart();
      }
    });

    // Get Quote button
    document.querySelector(".quote-btn").addEventListener("click", () => {
      document.getElementById("contact").scrollIntoView({ behavior: "smooth" });
    });

    // Contact button
    document.querySelector(".contact-btn").addEventListener("click", () => {
      alert("Contact form will be displayed here!");
    });
  }

  // Save cart data to session via AJAX
  function saveCartToSession() {
    // Create form data
    const formData = new FormData();
    formData.append("action", "save_cart");
    formData.append("cart_items", JSON.stringify(state.cart.items));
    formData.append("cart_total", state.cart.calculateTotal());

    // Send AJAX request
    fetch("save_cart.php", {
      method: "POST",
      body: formData,
    }).catch((error) => {
      console.error("Error saving cart:", error);
    });
  }

  // Initialize the page
  document.addEventListener("DOMContentLoaded", () => {
    initEventListeners();
    state.cart.updateCartUI(); // Initialize cart UI
  });
})();
