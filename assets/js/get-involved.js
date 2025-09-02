// Get Involved form functionality
document.addEventListener('DOMContentLoaded', function() {
    initGetInvolvedPage();
});

function initGetInvolvedPage() {
    initPartnershipForm();
    initVolunteerForm();
    initSmoothScrollToForms();
}

function initPartnershipForm() {
    const form = document.getElementById('partnershipForm');
    if (!form) return;

    // Add real-time validation
    addFormValidation(form, 'partnership');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        handleFormSubmission(e, 'partnership');
    });
}

function initVolunteerForm() {
    const form = document.getElementById('volunteerForm');
    if (!form) return;

    // Add real-time validation
    addFormValidation(form, 'volunteer');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        handleFormSubmission(e, 'volunteer');
    });
}

function addFormValidation(form, formType) {
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // Validate on blur
        input.addEventListener('blur', function() {
            validateField(this, formType);
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
    });
}

function validateField(field, formType) {
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
    if (value) {
        switch (field.type) {
            case 'email':
                if (!isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;
                
            case 'text':
                if (field.name === 'name' && value.length < 2) {
                    isValid = false;
                    errorMessage = 'Name must be at least 2 characters';
                }
                break;
        }
        
        // Textarea validations
        if (field.tagName === 'TEXTAREA') {
            const minLength = getTextareaMinLength(field.name, formType);
            if (isRequired && value.length < minLength) {
                isValid = false;
                errorMessage = `Please provide at least ${minLength} characters`;
            }
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

function getTextareaMinLength(fieldName, formType) {
    const minLengths = {
        partnership: {
            message: 50
        },
        volunteer: {
            expertise: 30,
            motivation: 20,
            availability: 10
        }
    };
    
    return minLengths[formType]?.[fieldName] || 10;
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

async function handleFormSubmission(e, formType) {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.disabled = true;
    const loadingText = formType === 'partnership' ? 'Submitting Partnership Inquiry...' : 'Submitting Volunteer Application...';
    submitButton.innerHTML = `<i data-lucide="loader-2"></i> ${loadingText}`;
    
    // Re-initialize icons for the loading spinner
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    try {
        // Validate entire form
        if (!validateForm(form, formType)) {
            throw new Error('Please correct the errors below and try again');
        }
        
        // Prepare form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.form_type = formType; // Add form type identifier
        
        // Determine API endpoint
        const apiEndpoint = formType === 'partnership' ? 'api/submit_partnership.php' : 'api/submit_volunteer.php';
        
        // Submit form
        const response = await fetch(apiEndpoint, {
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
            showFormSuccess(form, formType);
            
            // Clear form
            form.reset();
            clearAllFieldErrors(form);
            
            // Scroll to success message
            setTimeout(() => {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            
        } else {
            throw new Error(result.message || `${formType} submission failed`);
        }
        
    } catch (error) {
        console.error(`${formType} form submission error:`, error);
        
        // Show error notification
        if (Utils && Utils.showNotification) {
            Utils.showNotification(error.message, 'error');
        } else {
            alert('Error: ' + error.message);
        }
        
        // Scroll to first error if any
        const firstError = form.querySelector('.error');
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

function validateForm(form, formType) {
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    // Clear all previous errors
    clearAllFieldErrors(form);
    
    // Validate each field
    inputs.forEach(input => {
        if (!validateField(input, formType)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function clearAllFieldErrors(form) {
    const errors = form.querySelectorAll('.error-message');
    const fields = form.querySelectorAll('.error');
    
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
    });
    
    fields.forEach(field => {
        field.classList.remove('error');
    });
}

function showFormSuccess(form, formType) {
    const successMessages = {
        partnership: {
            title: 'Partnership Inquiry Submitted!',
            message: 'Thank you for your interest in partnering with Fashion Nexus Ghana. We have received your inquiry and a member of our partnerships team will contact you within 3-5 business days to discuss opportunities tailored to your interests.',
            icon: 'handshake'
        },
        volunteer: {
            title: 'Volunteer Application Submitted!',
            message: 'Thank you for your interest in volunteering with Fashion Nexus Ghana. We have received your application and will review it carefully. Our volunteer coordinator will be in touch within one week to discuss next steps.',
            icon: 'heart'
        }
    };
    
    const success = successMessages[formType];
    
    // Create success message HTML
    const successHTML = `
        <div class="form-success-message">
            <div class="success-icon">
                <i data-lucide="${success.icon}"></i>
            </div>
            <h3>${success.title}</h3>
            <p>${success.message}</p>
            <div class="success-actions">
                <a href="index.html" class="btn btn-primary">Return to Homepage</a>
                <a href="contact.html" class="btn btn-secondary">Contact Us</a>
            </div>
        </div>
    `;
    
    // Replace form with success message
    const formContainer = form.parentNode;
    if (formContainer) {
        formContainer.innerHTML = successHTML;
        
        // Re-initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function initSmoothScrollToForms() {
    // Handle links that point to form sections
    const formLinks = document.querySelectorAll('a[href="#partnership-form"], a[href="#sponsorship-form"], a[href="#volunteer-form"]');
    
    formLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            let targetElement;
            
            // Map different link targets to actual form sections
            switch (targetId) {
                case 'partnership-form':
                case 'sponsorship-form':
                    targetElement = document.getElementById('partnership-form');
                    break;
                case 'volunteer-form':
                    targetElement = document.getElementById('volunteer-form');
                    break;
            }
            
            if (targetElement) {
                const offsetTop = targetElement.offsetTop - 100; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Focus the first input in the form
                setTimeout(() => {
                    const firstInput = targetElement.querySelector('input:not([type="hidden"]), select, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 500);
            }
        });
    });
}

// Analytics tracking for form interactions (optional)
function trackFormInteraction(action, formType, additionalData = {}) {
    // This is a placeholder for analytics tracking
    // Replace with your preferred analytics solution (Google Analytics, etc.)
    if (typeof gtag !== 'undefined') {
        gtag('event', action, {
            event_category: 'Form Interaction',
            event_label: formType,
            ...additionalData
        });
    }
    
    console.log('Form Interaction:', { action, formType, ...additionalData });
}

// Track when users start filling forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = ['partnershipForm', 'volunteerForm'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            let hasStartedFilling = false;
            
            form.addEventListener('input', function() {
                if (!hasStartedFilling) {
                    hasStartedFilling = true;
                    trackFormInteraction('form_start', formId.replace('Form', ''));
                }
            });
        }
    });
});

// Export functions for testing or external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateField,
        validateEmail,
        isValidEmail
    };
}