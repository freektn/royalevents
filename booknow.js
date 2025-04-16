document.addEventListener("DOMContentLoaded", function () {
  // Form validation
  const bookingForm = document.getElementById("bookingForm");

  if (bookingForm) {
    bookingForm.addEventListener("submit", function (event) {
      let isValid = true;

      // Validate full name
      const fullname = document.getElementById("fullname");
      if (!fullname.value.trim()) {
        showError(fullname, "Le nom et prénom sont requis");
        isValid = false;
      } else {
        clearError(fullname);
      }

      // Validate phone number (8 digits for Tunisia)
      const phone = document.getElementById("phone");
      if (!phone.value.trim()) {
        showError(phone, "Le numéro de téléphone est requis");
        isValid = false;
      } else if (!/^[0-9]{8}$/.test(phone.value.trim())) {
        showError(phone, "Le numéro de téléphone doit contenir 8 chiffres");
        isValid = false;
      } else {
        clearError(phone);
      }

      // Validate event date (must be in the future)
      const eventDate = document.getElementById("event_date");
      if (!eventDate.value) {
        showError(eventDate, "La date est requise");
        isValid = false;
      } else {
        const selectedDate = new Date(eventDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
          showError(eventDate, "La date doit être dans le futur");
          isValid = false;
        } else {
          clearError(eventDate);
        }
      }

      // Validate event type
      const eventType = document.getElementById("event_type");
      if (!eventType.value) {
        showError(eventType, "Le type d'événement est requis");
        isValid = false;
      } else {
        clearError(eventType);
      }

      // Validate location
      const location = document.getElementById("location");
      if (!location.value.trim()) {
        showError(location, "La localisation est requise");
        isValid = false;
      } else {
        clearError(location);
      }

      if (!isValid) {
        event.preventDefault();
      }
    });

    // Set minimum date for event date input to today
    const eventDateInput = document.getElementById("event_date");
    if (eventDateInput) {
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, "0");
      const dd = String(today.getDate()).padStart(2, "0");
      const todayString = `${yyyy}-${mm}-${dd}`;

      eventDateInput.setAttribute("min", todayString);
    }
  }

  // Helper functions for form validation
  function showError(input, message) {
    // Remove any existing error message
    clearError(input);

    // Create and add error message
    const errorElement = document.createElement("span");
    errorElement.className = "error-message";
    errorElement.textContent = message;

    input.parentNode.appendChild(errorElement);
    input.classList.add("error-input");
  }

  function clearError(input) {
    const parent = input.parentNode;
    const errorElement = parent.querySelector(".error-message");

    if (errorElement) {
      parent.removeChild(errorElement);
    }

    input.classList.remove("error-input");
  }
});
