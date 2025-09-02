// Registration form functionality
document.addEventListener('DOMContentLoaded', function() {
    initRegistrationForm();
});

function initRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    // Add real-time validation
    addRealTimeValidation();
    
    // Handle form submission
    form.addEventListener('submit', handleFormSubmission);
    
    // Initialize character counters for textareas
    initCharacterCounters();
    
    // Auto-save form data (optional)
    initAutoSave();
}

function addRealTimeValidation() {
    const inputs = document.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // Validate on blur
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        // Clear errors on focus
        input.addEventListener('focus', function() {
            clearFieldError(this);
        });
        
        // Special handling for email
        if (input.type === 'email') {
            input.addEventListener('input', function() {
                if (this.value.length > 0) {
                    validateEmail(this);
                }
            });
        }
        
        // Special handling for phone
        if (input.type === 'tel') {
            input.addEventListener('input', function() {
                formatPhoneNumber(this);
            });
        }
    });
}

function validateField(field) {
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
    
    if (value && field.name === 'first_name') {
        if (value.length < 2) {
            isValid = false;
            errorMessage = 'First name must be at least 2 characters';
        }
    }
    
    if (value && field.name === 'last_name') {
        if (value.length < 2) {
            isValid = false;
            errorMessage = 'Last name must be at least 2 characters';
        }
    }
    
    if (field.tagName === 'TEXTAREA') {
        const minLength = field.name === 'motivation' || field.name === 'expectations' ? 50 : 0;
        if (isRequired && value.length < minLength) {
            isValid = false;
            errorMessage = `Please provide at least ${minLength} characters`;
        }
    }
    
    // Show/hide error
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function validateEmail(field) {
    const email = field.value.trim();
    
    if (email && !isValidEmail(email)) {
        showFieldError(field, 'Please enter a valid email address');
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function formatPhoneNumber(field) {
    // Basic phone number formatting (can be enhanced)
    let value = field.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        if (value.startsWith('233')) {
            // Ghana format
            value = value.replace(/(\d{3})(\d{2})(\d{3})(\d{4})/, '+$1 $2 $3 $4');
        } else if (value.length === 10) {
            // US format
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
    }
    
    field.value = value;
}

function showFieldError(field, message) {
    field.classList.add('error');
    const errorElement = field.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
}

function initCharacterCounters() {
    const textareas = document.querySelectorAll('textarea');
    
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
            const counter = document.createElement('div');
            counter.className = 'character-counter';
            counter.style.cssText = 'text-align: right; font-size: 0.8rem; color: var(--medium-gray); margin-top: 4px;';
            
            const updateCounter = () => {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${remaining} characters remaining`;
                counter.style.color = remaining < 50 ? 'var(--primary-color)' : 'var(--medium-gray)';
            };
            
            textarea.addEventListener('input', updateCounter);
            textarea.parentNode.appendChild(counter);
            updateCounter();
        }
    });
}

function initAutoSave() {
    const form = document.getElementById('registrationForm');
    const autoSaveKey = 'bogf_registration_draft';
    
    // Load saved data
    const savedData = localStorage.getItem(autoSaveKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            populateFormData(data);
            
            // Show notification about restored data
            setTimeout(() => {
                if (Utils && Utils.showNotification) {
                    Utils.showNotification('Draft application restored from previous session', 'info');
                }
            }, 1000);
        } catch (e) {
            console.warn('Failed to restore form data:', e);
        }
    }
    
    // Save data on change
    let saveTimeout;
    form.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(autoSaveKey, JSON.stringify(data));
        }, 1000);
    });
    
    // Clear saved data on successful submission
    form.addEventListener('formSuccess', function() {
        localStorage.removeItem(autoSaveKey);
    });
}

function populateFormData(data) {
    for (const [name, value] of Object.entries(data)) {
        const field = document.querySelector(`[name="${name}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === true;
            } else {
                field.value = value;
            }
        }
    }
}

async function handleFormSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<i data-lucide="loader-2"></i> Submitting Application...';
    
    // Re-initialize icons for the loading spinner
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    try {
        // Validate entire form
        if (!validateForm()) {
            throw new Error('Please correct the errors below and try again');
        }
        
        // Prepare form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Submit application
        const response = await fetch('api/submit_application.php', {
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
            // Success - show modal and dispatch custom event
            showSuccessModal();
            form.dispatchEvent(new CustomEvent('formSuccess'));
            
            // Reset form
            form.reset();
            clearAllFieldErrors();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
        } else {
            throw new Error(result.message || 'Application submission failed');
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        
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

function validateForm() {
    const form = document.getElementById('registrationForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    // Clear all previous errors
    clearAllFieldErrors();
    
    // Validate each field
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    // Special validation for privacy consent
    const privacyConsent = document.getElementById('privacyConsent');
    if (!privacyConsent.checked) {
        const consentError = document.getElementById('consentError');
        if (consentError) {
            consentError.textContent = 'You must agree to the Privacy Policy and Terms of Service';
            consentError.style.display = 'block';
        }
        isValid = false;
    }
    
    return isValid;
}

function clearAllFieldErrors() {
    const errors = document.querySelectorAll('.error-message');
    const fields = document.querySelectorAll('.error');
    
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
    });
    
    fields.forEach(field => {
        field.classList.remove('error');
    });
}

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Re-initialize icons in modal
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('successModal');
    if (modal && e.target === modal) {
        closeSuccessModal();
    }
});

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSuccessModal();
    }
});

// Form validation messages
const validationMessages = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    minLength: 'This field is too short',
    maxLength: 'This field is too long',
    pattern: 'Please enter a valid format'
};

// Export functions for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateField,
        validateEmail,
        isValidEmail,
        formatPhoneNumber
    };
}