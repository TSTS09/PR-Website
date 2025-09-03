<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Featured Voices - Meet the speakers and thought leaders at Business of Ghanaian Fashion Summit 2025">
    <title>Featured Voices | Business of Ghanaian Fashion</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.html">
                    <h2 class="logo-text">Fashion Nexus <span class="highlight">Ghana</span></h2>
                </a>
            </div>
            
            <ul class="nav-menu" id="nav-menu">
                <li class="nav-item">
                    <a href="about.html" class="nav-link">About Us</a>
                </li>
                <li class="nav-item">
                    <a href="mission.html" class="nav-link">Mission</a>
                </li>
                <li class="nav-item">
                    <a href="summit.html" class="nav-link">The Summit 2025</a>
                </li>
                <li class="nav-item">
                    <a href="speakers.html" class="nav-link active">Featured Voices</a>
                </li>
                <li class="nav-item">
                    <a href="programs.html" class="nav-link">Programs</a>
                </li>
                <li class="nav-item">
                    <a href="partners.html" class="nav-link">Partners</a>
                </li>
                <li class="nav-item">
                    <a href="get-involved.html" class="nav-link">Get Involved</a>
                </li>
                <li class="nav-item">
                    <a href="resources.html" class="nav-link">Resources</a>
                </li>
                <li class="nav-item">
                    <a href="contact.html" class="nav-link">Contact</a>
                </li>
            </ul>
            
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <h1>Featured Voices</h1>
                <p class="header-subtitle">Meet the visionary leaders, innovators, and change-makers driving Ghana's fashion revolution at our 2025 Summit</p>
            </div>
        </div>
    </section>

    <!-- Speakers Filter -->
    <section class="speakers-filter">
        <div class="container">
            <div class="filter-tabs">
                <button class="filter-tab active" data-category="all">All Speakers</button>
                <button class="filter-tab" data-category="keynote">Keynote Speakers</button>
                <button class="filter-tab" data-category="panelist">Panelists</button>
                <button class="filter-tab" data-category="moderator">Moderators</button>
                <button class="filter-tab" data-category="roundtable">Roundtable Leads</button>
            </div>
        </div>
    </section>

    <!-- Speakers Grid -->
    <section class="speakers-section">
        <div class="container">
            <div class="speakers-grid" id="speakersGrid">
                <!-- Speakers will be loaded dynamically -->
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading our distinguished speakers...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="speakers-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Join These Industry Leaders</h2>
                <p>Be part of the conversation shaping Ghana's fashion future</p>
                <a href="register.html" class="btn btn-primary">Register for Summit 2025</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Business of Ghanaian Fashion</h3>
                    <p>Building a sustainable future for Ghana's fashion industry through connection, creativity, and coordinated action.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Instagram"><i data-lucide="instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i data-lucide="linkedin"></i></a>
                        <a href="#" aria-label="YouTube"><i data-lucide="youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="summit.html">Summit 2025</a></li>
                        <li><a href="programs.html">Programs</a></li>
                        <li><a href="get-involved.html">Get Involved</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="resources.html">Media & Resources</a></li>
                        <li><a href="speakers.html">Featured Voices</a></li>
                        <li><a href="partners.html">Partners</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@fashionnexusghana.com</p>
                    <a href="register.html" class="btn btn-sm btn-primary">Register Now</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Business of Ghanaian Fashion. Presented by Fashion Nexus Ghana.</p>
                <div class="footer-links">
                    <a href="privacy.html">Privacy Policy</a>
                    <a href="terms.html">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Speaker Detail Modal -->
    <div class="modal" id="speakerModal">
        <div class="modal-content">
            <div class="modal-header">
                <button class="modal-close" id="modalClose">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Speaker details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/speakers.js"></script>
</body>
</html>