// Tool-Kart Main JavaScript Functions

// Form validation functions
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#DC3545';
    errorDiv.style.fontSize = '0.9rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#DC3545';
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

// Date validation for rental forms
function validateRentalDates() {
    const startDate = document.getElementById('rental_start_date');
    const endDate = document.getElementById('rental_end_date');
    
    if (!startDate || !endDate) return true;
    
    const today = new Date();
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    
    today.setHours(0, 0, 0, 0);
    
    if (start < today) {
        showFieldError(startDate, 'Start date cannot be in the past');
        return false;
    }
    
    if (end <= start) {
        showFieldError(endDate, 'End date must be after start date');
        return false;
    }
    
    clearFieldError(startDate);
    clearFieldError(endDate);
    return true;
}

// Shopping cart functions
function addToCart(toolId, toolName) {
    const startDate = document.getElementById('rental_start_date');
    const endDate = document.getElementById('rental_end_date');
    
    if (!startDate || !endDate || !startDate.value || !endDate.value) {
        alert('Please select rental dates before adding to cart');
        return false;
    }
    
    if (!validateRentalDates()) {
        return false;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('tool_id', toolId);
    formData.append('rental_start_date', startDate.value);
    formData.append('rental_end_date', endDate.value);
    
    // Send AJAX request
    fetch('modules/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            updateCartCount();
            showAlert('success', `${toolName} added to cart successfully!`);
        } else {
            showAlert('error', data.message || 'Failed to add item to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while adding to cart');
    })
    .finally(() => {
        // Reset button state
        button.textContent = originalText;
        button.disabled = false;
    });
    
    return false;
}

// Update cart count in header
function updateCartCount() {
    fetch('modules/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const cartIcon = document.querySelector('.cart-icon');
        const cartCount = cartIcon.querySelector('.cart-count');
        
        if (data.count > 0) {
            if (cartCount) {
                cartCount.textContent = data.count;
            } else {
                const countSpan = document.createElement('span');
                countSpan.className = 'cart-count';
                countSpan.textContent = data.count;
                cartIcon.appendChild(countSpan);
            }
        } else if (cartCount) {
            cartCount.remove();
        }
    })
    .catch(error => console.error('Error updating cart count:', error));
}

// Show alert messages
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
        <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
    `;
    
    // Insert at the beginning of main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Tool filtering functionality
function filterTools() {
    const categoryFilter = document.getElementById('category_filter');
    const searchInput = document.getElementById('search_input');
    
    if (!categoryFilter && !searchInput) return;
    
    const categoryValue = categoryFilter ? categoryFilter.value : '';
    const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
    
    const toolCards = document.querySelectorAll('.tool-card');
    
    toolCards.forEach(card => {
        const toolCategory = card.dataset.category || '';
        const toolName = card.querySelector('.tool-name').textContent.toLowerCase();
        const toolDescription = card.querySelector('.tool-description').textContent.toLowerCase();
        
        const categoryMatch = !categoryValue || toolCategory === categoryValue;
        const searchMatch = !searchValue || 
            toolName.includes(searchValue) || 
            toolDescription.includes(searchValue);
        
        if (categoryMatch && searchMatch) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Remove item from cart
function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('cart_id', cartId);
    
    fetch('modules/remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload page to update cart
        } else {
            showAlert('error', data.message || 'Failed to remove item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while removing item');
    });
}

// Calculate rental total
function calculateRentalTotal() {
    const startDate = document.getElementById('rental_start_date');
    const endDate = document.getElementById('rental_end_date');
    const dailyRate = document.getElementById('daily_rate');
    
    if (!startDate || !endDate || !dailyRate || !startDate.value || !endDate.value) {
        return;
    }
    
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const rate = parseFloat(dailyRate.value);
    
    if (end > start) {
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        const total = days * rate;
        
        const totalElement = document.getElementById('rental_total');
        if (totalElement) {
            totalElement.textContent = `₹${total.toFixed(2)} (${days} days)`;
        }
    }
}

// Initialize page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for rental date calculations
    const startDateInput = document.getElementById('rental_start_date');
    const endDateInput = document.getElementById('rental_end_date');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', calculateRentalTotal);
        endDateInput.addEventListener('change', calculateRentalTotal);
    }
    
    // Add event listeners for tool filtering
    const categoryFilter = document.getElementById('category_filter');
    const searchInput = document.getElementById('search_input');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterTools);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTools);
    }
    
    // Set minimum date for rental inputs to today
    const today = new Date().toISOString().split('T')[0];
    if (startDateInput) {
        startDateInput.min = today;
    }
    if (endDateInput) {
        endDateInput.min = today;
    }
    
    // Initialize on-scroll animations
    initScrollAnimations();
});

// Star rating functionality for reviews
function setRating(rating) {
    const stars = document.querySelectorAll('.star-rating .star');
    const ratingInput = document.getElementById('rating');
    
    if (ratingInput) {
        ratingInput.value = rating;
    }
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
            star.innerHTML = '★';
        } else {
            star.classList.remove('active');
            star.innerHTML = '☆';
        }
    });
}

// Initialize star rating display
function initStarRating() {
    const starRatings = document.querySelectorAll('.star-rating');
    
    starRatings.forEach(rating => {
        const ratingValue = parseInt(rating.dataset.rating || 0);
        const stars = rating.querySelectorAll('.star');
        
        stars.forEach((star, index) => {
            if (index < ratingValue) {
                star.innerHTML = '★';
                star.style.color = '#FFC107';
            } else {
                star.innerHTML = '☆';
                star.style.color = '#E9ECEF';
            }
        });
    });
}

// On-scroll animation functionality
function initScrollAnimations() {
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    
    if (animateElements.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(element => {
        observer.observe(element);
    });
}