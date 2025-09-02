// Speakers page functionality
document.addEventListener('DOMContentLoaded', function() {
    initSpeakersPage();
});

let allSpeakers = [];
let currentFilter = 'all';

function initSpeakersPage() {
    loadSpeakers();
    initFilterTabs();
    initModal();
}

// Load speakers from the database
async function loadSpeakers() {
    try {
        const response = await fetch('api/get_speakers.php');
        const data = await response.json();
        
        if (data.success) {
            allSpeakers = data.speakers;
            displaySpeakers(allSpeakers);
        } else {
            showError('Failed to load speakers. Please try again later.');
        }
    } catch (error) {
        console.error('Error loading speakers:', error);
        showError('Failed to load speakers. Please check your connection.');
    }
}

// Display speakers in the grid
function displaySpeakers(speakers) {
    const grid = document.getElementById('speakersGrid');
    
    if (speakers.length === 0) {
        grid.innerHTML = `
            <div class="no-speakers">
                <div class="no-speakers-icon">
                    <i data-lucide="users"></i>
                </div>
                <h3>No Speakers Found</h3>
                <p>We're still confirming our amazing lineup of speakers. Check back soon!</p>
            </div>
        `;
        return;
    }
    
    const speakersHTML = speakers.map(speaker => createSpeakerCard(speaker)).join('');
    grid.innerHTML = speakersHTML;
    
    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Add click event listeners to speaker cards
    const speakerCards = document.querySelectorAll('.speaker-card');
    speakerCards.forEach(card => {
        card.addEventListener('click', function() {
            const speakerId = this.dataset.speakerId;
            const speaker = allSpeakers.find(s => s.id == speakerId);
            if (speaker) {
                showSpeakerModal(speaker);
            }
        });
    });
}

// Create speaker card HTML
function createSpeakerCard(speaker) {
    return `
        <div class="speaker-card" data-speaker-id="${speaker.id}" data-category="${speaker.category}">
            <div class="speaker-image">
                <img src="${speaker.image_url || 'assets/images/speakers/default-speaker.jpg'}" 
                     alt="${speaker.name}" 
                     loading="lazy"
                     onerror="this.src='assets/images/speakers/default-speaker.jpg'">
                <div class="speaker-overlay">
                    <div class="speaker-category">${formatCategory(speaker.category)}</div>
                </div>
            </div>
            <div class="speaker-info">
                <h3 class="speaker-name">${speaker.name}</h3>
                <p class="speaker-title">${speaker.title}</p>
                <p class="speaker-organization">${speaker.organization}</p>
                <div class="speaker-bio-preview">
                    ${truncateBio(speaker.bio, 100)}
                </div>
                <div class="speaker-links">
                    ${speaker.linkedin_url ? `<a href="${speaker.linkedin_url}" target="_blank" class="speaker-link"><i data-lucide="linkedin"></i></a>` : ''}
                    ${speaker.twitter_url ? `<a href="${speaker.twitter_url}" target="_blank" class="speaker-link"><i data-lucide="twitter"></i></a>` : ''}
                    ${speaker.website_url ? `<a href="${speaker.website_url}" target="_blank" class="speaker-link"><i data-lucide="external-link"></i></a>` : ''}
                </div>
            </div>
            <div class="speaker-card-footer">
                <button class="btn btn-sm btn-outline">View Full Profile</button>
            </div>
        </div>
    `;
}

// Initialize filter tabs
function initFilterTabs() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get filter category
            const category = this.dataset.category;
            currentFilter = category;
            
            // Filter speakers
            filterSpeakers(category);
        });
    });
}

// Filter speakers by category
function filterSpeakers(category) {
    let filteredSpeakers;
    
    if (category === 'all') {
        filteredSpeakers = allSpeakers;
    } else {
        filteredSpeakers = allSpeakers.filter(speaker => 
            speaker.category.toLowerCase() === category.toLowerCase()
        );
    }
    
    displaySpeakers(filteredSpeakers);
}

// Initialize modal functionality
function initModal() {
    const modal = document.getElementById('speakerModal');
    const closeBtn = document.getElementById('modalClose');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSpeakerModal);
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeSpeakerModal();
        }
    });
    
    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeSpeakerModal();
        }
    });
}

// Show speaker modal
function showSpeakerModal(speaker) {
    const modal = document.getElementById('speakerModal');
    const modalBody = document.getElementById('modalBody');
    
    modalBody.innerHTML = createSpeakerModalContent(speaker);
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Close speaker modal
function closeSpeakerModal() {
    const modal = document.getElementById('speakerModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

// Create speaker modal content
function createSpeakerModalContent(speaker) {
    return `
        <div class="speaker-modal-content">
            <div class="speaker-modal-header">
                <div class="speaker-modal-image">
                    <img src="${speaker.image_url || 'assets/images/speakers/default-speaker.jpg'}" 
                         alt="${speaker.name}"
                         onerror="this.src='assets/images/speakers/default-speaker.jpg'">
                </div>
                <div class="speaker-modal-info">
                    <div class="speaker-category-badge">${formatCategory(speaker.category)}</div>
                    <h2>${speaker.name}</h2>
                    <p class="speaker-modal-title">${speaker.title}</p>
                    <p class="speaker-modal-organization">${speaker.organization}</p>
                    <div class="speaker-modal-links">
                        ${speaker.linkedin_url ? `<a href="${speaker.linkedin_url}" target="_blank" class="btn btn-sm btn-outline"><i data-lucide="linkedin"></i> LinkedIn</a>` : ''}
                        ${speaker.twitter_url ? `<a href="${speaker.twitter_url}" target="_blank" class="btn btn-sm btn-outline"><i data-lucide="twitter"></i> Twitter</a>` : ''}
                        ${speaker.website_url ? `<a href="${speaker.website_url}" target="_blank" class="btn btn-sm btn-outline"><i data-lucide="external-link"></i> Website</a>` : ''}
                    </div>
                </div>
            </div>
            
            <div class="speaker-modal-body">
                <div class="speaker-bio">
                    <h3>Biography</h3>
                    <div class="bio-content">
                        ${formatBio(speaker.bio)}
                    </div>
                </div>
                
                ${speaker.expertise ? `
                    <div class="speaker-expertise">
                        <h3>Areas of Expertise</h3>
                        <div class="expertise-tags">
                            ${speaker.expertise.split(',').map(exp => `<span class="expertise-tag">${exp.trim()}</span>`).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${speaker.session_title ? `
                    <div class="speaker-session">
                        <h3>Session</h3>
                        <h4>${speaker.session_title}</h4>
                        ${speaker.session_description ? `<p>${speaker.session_description}</p>` : ''}
                        ${speaker.session_time ? `
                            <div class="session-time">
                                <i data-lucide="clock"></i>
                                <span>${speaker.session_time}</span>
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

// Utility functions
function formatCategory(category) {
    const categoryMap = {
        'keynote': 'Keynote Speaker',
        'panelist': 'Panelist',
        'moderator': 'Moderator',
        'roundtable': 'Roundtable Lead',
        'host': 'Session Host',
        'guest': 'Special Guest'
    };
    
    return categoryMap[category.toLowerCase()] || category;
}

function truncateBio(bio, maxLength) {
    if (!bio) return '';
    
    if (bio.length <= maxLength) {
        return bio;
    }
    
    return bio.substring(0, maxLength).trim() + '...';
}

function formatBio(bio) {
    if (!bio) return '<p>Biography coming soon...</p>';
    
    // Convert line breaks to paragraphs
    return bio.split('\n\n').map(paragraph => 
        paragraph.trim() ? `<p>${paragraph.trim()}</p>` : ''
    ).join('');
}

function showError(message) {
    const grid = document.getElementById('speakersGrid');
    grid.innerHTML = `
        <div class="error-message">
            <div class="error-icon">
                <i data-lucide="alert-circle"></i>
            </div>
            <h3>Unable to Load Speakers</h3>
            <p>${message}</p>
            <button class="btn btn-primary" onclick="loadSpeakers()">Try Again</button>
        </div>
    `;
    
    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}