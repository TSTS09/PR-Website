// Contact form functionality
document.addEventListener('DOMContentLoaded', function() {
    initContactForm();
    initFAQ();
});

function initContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    // Add real-time validation
    addContactFormValidation();
    
    // Handle form submission
    form.addEventListener('submit', handleContactFormSubmission);
}

function addContactFormValidation() {
    const inputs = document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea');
    
    inputs.forEach(input => {
        // Validate on blur
        input.addEventListener('blur', function() {
            validateContactField(this);
        });
        
        // Clear errors on focus
        input.addEventListener('focus', function() {
            clearContactFieldError(this);
        });
        
        // Special handling for email
        if (input.type === 'email') {
            input.addEventListener('input', function() {
                if (this.value.length > 0) {
                    validateContactEmail(this);
                }
            });
        }
    });
}

function validateContactField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (isRequired && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Specific field validations
    if (value && field.type === 'email') {
        if (!isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    if (value && field.name === 'name') {
        if (value.length < 2) {
            isValid = false;
            errorMessage = 'Name must be at least 2 characters';
        }
    }
    
    if (field.tagName === 'TEXTAREA' && value.length > 0 && value.length < 10) {
        isValid = false;
        errorMessage = 'Please provide a more detailed message';
    }
    
    // Show/hide error
    if (!isValid) {
        showContactFieldError(field, errorMessage);
    } else {
        clearContactFieldError(field);
    }
    
    return isValid;
}

function validateContactEmail(field) {
    const email = field.value.trim();
    
    if (email && !isValidEmail(email)) {
        showContactFieldError(field, 'Please enter a valid email address');
        return false;
    } else {
        clearContactFieldError(field);
        return true;
    }
}

function showContactFieldError(field, message) {
    field.classList.add('error');
    const errorElement = field.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearContactFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
}

async function handleContactFormSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i data-lucide="loader-2"></i> Sending Message...';
    
    // Re-initialize icons for the loading spinner
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    try {
        // Validate entire form
        if (!validateContactForm()) {
            throw new Error('Please correct the errors below and try again');
        }
        
        // Prepare form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Submit message
        const response = await fetch('api/submit_contact.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Network error occurred');
        }
        
        if (result.success) {
            // Success - show success message
            showContactSuccess();
            
            // Reset form
            form.reset();
            clearAllContactFieldErrors();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
        } else {
            throw new Error(result.message || 'Message submission failed');
        }
        
    } catch (error) {
        console.error('Contact form submission error:', error);
        
        // Show error notification
        if (Utils && Utils.showNotification) {
            Utils.showNotification(error.message, 'error');
        } else {
            alert('Error: ' + error.message);
        }
        
        // Scroll to first error if any
        const firstError = document.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
    } finally {
        // Restore button state
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        
        // Re-initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function validateContactForm() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    // Clear all previous errors
    clearAllContactFieldErrors();
    
    // Validate each field
    inputs.forEach(input => {
        if (!validateContactField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function clearAllContactFieldErrors() {
    const errors = document.querySelectorAll('#contactForm .error-message');
    const fields = document.querySelectorAll('#contactForm .error');
    
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
    });
    
    fields.forEach(field => {
        field.classList.remove('error');
    });
}

function showContactSuccess() {
    // Create success message
    const successHTML = `
        <div class="contact-success">
            <div class="success-icon">
                <i data-lucide="check-circle"></i>
            </div>
            <h3>Message Sent Successfully!</h3>
            <p>Thank you for contacting Fashion Nexus Ghana. We have received your message and will get back to you within 24-48 hours.</p>
        </div>
    `;
    
    // Replace form with success message
    const formContainer = document.querySelector('.form-container');
    if (formContainer) {
        formContainer.innerHTML = successHTML;
        
        // Re-initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// FAQ functionality
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        if (question && answer) {
            // Set initial state
            answer.style.maxHeight = '0';
            answer.style.overflow = 'hidden';
            answer.style.transition = 'max-height 0.3s ease';
        }
    });
}

function toggleFaq(questionElement) {
    const faqItem = questionElement.parentNode;
    const answer = faqItem.querySelector('.faq-answer');
    const icon = questionElement.querySelector('i');
    
    if (!answer || !icon) return;
    
    const isOpen = faqItem.classList.contains('active');
    
    // Close all other FAQ items
    const allFaqItems = document.querySelectorAll('.faq-item');
    allFaqItems.forEach(item => {
        if (item !== faqItem) {
            item.classList.remove('active');
            const otherAnswer = item.querySelector('.faq-answer');
            const otherIcon = item.querySelector('.faq-question i');
            
            if (otherAnswer) {
                otherAnswer.style.maxHeight = '0';
            }
            
            if (otherIcon) {
                otherIcon.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Toggle current FAQ item
    if (isOpen) {
        // Close
        faqItem.classList.remove('active');
        answer.style.maxHeight = '0';
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Open
        faqItem.classList.add('active');
        answer.style.maxHeight = answer.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
    }
}

// Map functionality
function openMap() {
    // You can replace this with actual coordinates for Fashion Nexus Ghana
    const coordinates = '5.6037,-0.1870'; // Accra coordinates
    const mapUrl = `https://www.google.com/maps/search/?api=1&query=${coordinates}`;
    window.open(mapUrl, '_blank');
}

// Utility function for email validation (if not available from main.js)
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}