/**
 * MTICS Main JavaScript
 */

// Mobile menu toggle
document.addEventListener("DOMContentLoaded", function () {
  const mobileToggle = document.querySelector(".mobile-menu-toggle");
  const mainNav = document.querySelector(".main-nav");

  if (mobileToggle && mainNav) {
    mobileToggle.addEventListener("click", function () {
      mainNav.classList.toggle("active");
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (e) {
    if (
      mainNav &&
      mobileToggle &&
      !mainNav.contains(e.target) &&
      !mobileToggle.contains(e.target)
    ) {
      mainNav.classList.remove("active");
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");
      if (href !== "#" && href.length > 1) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      }
    });
  });

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.transition = "opacity 0.5s ease";
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });

  // Form validation
  const forms = document.querySelectorAll("form[data-validate]");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });
  });
});

function validateForm(form) {
  let isValid = true;
  const inputs = form.querySelectorAll(
    "input[required], select[required], textarea[required]",
  );

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      isValid = false;
      input.classList.add("error");
      showFieldError(input, "This field is required");
    } else {
      input.classList.remove("error");
      clearFieldError(input);
    }
  });

  // Email validation
  const emailInputs = form.querySelectorAll('input[type="email"]');
  emailInputs.forEach((input) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (input.value && !emailRegex.test(input.value)) {
      isValid = false;
      input.classList.add("error");
      showFieldError(input, "Please enter a valid email address");
    }
  });

  // Password confirmation
  const passwordInput = form.querySelector('input[name="password"]');
  const confirmPasswordInput = form.querySelector(
    'input[name="confirm_password"]',
  );
  if (passwordInput && confirmPasswordInput && confirmPasswordInput.value) {
    if (passwordInput.value !== confirmPasswordInput.value) {
      isValid = false;
      confirmPasswordInput.classList.add("error");
      showFieldError(confirmPasswordInput, "Passwords do not match");
    }
  }

  return isValid;
}

function showFieldError(input, message) {
  clearFieldError(input);
  const errorDiv = document.createElement("div");
  errorDiv.className = "field-error";
  errorDiv.textContent = message;
  errorDiv.style.color = "#ff6b6b";
  errorDiv.style.fontSize = "0.875rem";
  errorDiv.style.marginTop = "0.25rem";
  input.parentNode.appendChild(errorDiv);
}

function clearFieldError(input) {
  const errorDiv = input.parentNode.querySelector(".field-error");
  if (errorDiv) {
    errorDiv.remove();
  }
}

// AJAX helper function
async function fetchAPI(url, options = {}) {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "An error occurred");
    }

    return data;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

// Token formatting
function formatTokens(amount) {
  return parseFloat(amount).toFixed(2);
}
