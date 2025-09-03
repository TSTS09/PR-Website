<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Fashion Nexus Ghana and the Business of Ghanaian Fashion team. Get in touch for partnerships, questions, or collaboration opportunities.">
    <title>Contact Us | Business of Ghanaian Fashion</title>
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
                    <a href="speakers.html" class="nav-link">Featured Voices</a>
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
                    <a href="contact.html" class="nav-link active">Contact</a>
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
                <h1>Contact Us</h1>
                <p class="header-subtitle">Get in touch with the Fashion Nexus Ghana team for partnerships, questions, or collaboration opportunities.</p>
            </div>
        </div>
    </section>

    <!-- Contact Options -->
    <section class="contact-options">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i data-lucide="mail"></i>
                    </div>
                    <h3>General Inquiries</h3>
                    <p>For general questions about our programs and initiatives.</p>
                    <a href="mailto:info@fashionnexusghana.com" class="contact-link">
                        info@fashionnexusghana.com
                    </a>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i data-lucide="calendar"></i>
                    </div>
                    <h3>Summit Registration</h3>
                    <p>Questions about the Business of Ghanaian Fashion Summit.</p>
                    <a href="mailto:summit@fashionnexusghana.com" class="contact-link">
                        summit@fashionnexusghana.com
                    </a>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i data-lucide="handshake"></i>
                    </div>
                    <h3>Partnerships</h3>
                    <p>Interested in partnering with us or sponsoring our initiatives.</p>
                    <a href="mailto:partnerships@fashionnexusghana.com" class="contact-link">
                        partnerships@fashionnexusghana.com
                    </a>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i data-lucide="newspaper"></i>
                    </div>
                    <h3>Media & Press</h3>
                    <p>Press inquiries and media partnership opportunities.</p>
                    <a href="mailto:media@fashionnexusghana.com" class="contact-link">
                        media@fashionnexusghana.com
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="contact-form-section">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2>Send Us a Message</h2>
                    <p>Have a specific question or collaboration idea? We'd love to hear from you.</p>
                </div>
                
                <form id="contactForm" class="contact-form" novalidate>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contactName">Your Name *</label>
                            <input type="text" id="contactName" name="name" required>
                            <div class="error-message"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contactEmail">Email Address *</label>
                            <input type="email" id="contactEmail" name="email" required>
                            <div class="error-message"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contactOrganization">Organization</label>
                            <input type="text" id="contactOrganization" name="organization">
                            <div class="error-message"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contactSubject">Subject *</label>
                            <select id="contactSubject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Summit Registration">Summit Registration</option>
                                <option value="Partnership Opportunity">Partnership Opportunity</option>
                                <option value="Sponsorship">Sponsorship</option>
                                <option value="Media Inquiry">Media Inquiry</option>
                                <option value="Speaking Opportunity">Speaking Opportunity</option>
                                <option value="Collaboration">Collaboration</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="error-message"></div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="contactMessage">Message *</label>
                        <textarea id="contactMessage" name="message" rows="6" required placeholder="Tell us more about your inquiry or how we can help..."></textarea>
                        <div class="error-message"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="send"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Office Information -->
    <section class="office-info">
        <div class="container">
            <h2 class="section-title center">Our Location</h2>
            <div class="office-grid">
                <div class="office-details">
                    <div class="office-card">
                        <h3>Fashion Nexus Ghana</h3>
                        <div class="office-item">
                            <i data-lucide="map-pin"></i>
                            <div>
                                <strong>Address</strong>
                                <span>Accra, Greater Accra Region<br>Ghana</span>
                            </div>
                        </div>
                        
                        <div class="office-item">
                            <i data-lucide="mail"></i>
                            <div>
                                <strong>Email</strong>
                                <span>info@fashionnexusghana.com</span>
                            </div>
                        </div>
                        
                        <div class="office-item">
                            <i data-lucide="clock"></i>
                            <div>
                                <strong>Business Hours</strong>
                                <span>Monday - Friday: 9:00 AM - 5:00 PM GMT<br>
                                Saturday - Sunday: Closed</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-section">
                        <h4>Follow Us</h4>
                        <p>Stay connected for updates on our programs and events.</p>
                        <div class="social-links-large">
                            <a href="#" class="social-link">
                                <i data-lucide="instagram"></i>
                                <span>Instagram</span>
                            </a>
                            <a href="#" class="social-link">
                                <i data-lucide="linkedin"></i>
                                <span>LinkedIn</span>
                            </a>
                            <a href="#" class="social-link">
                                <i data-lucide="youtube"></i>
                                <span>YouTube</span>
                            </a>
                            <a href="#" class="social-link">
                                <i data-lucide="twitter"></i>
                                <span>Twitter</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="map-placeholder">
                    <div class="map-content">
                        <i data-lucide="map"></i>
                        <h4>Accra, Ghana</h4>
                        <p>Fashion Nexus Ghana is based in Accra, the vibrant capital of Ghana and the heart of West Africa's creative economy.</p>
                        <button class="btn btn-outline" onclick="openMap()">
                            <i data-lucide="external-link"></i>
                            View on Map
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="section-title center">Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>How can I register for the Summit?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can register for the Business of Ghanaian Fashion Summit through our online registration form. Applications are reviewed carefully, and selected participants will be contacted with further instructions.</p>
                        <p><a href="register.html">Register here →</a></p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>What partnership opportunities are available?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer various partnership opportunities including summit sponsorship, program funding, strategic partnerships, and collaborative initiatives. Each partnership is tailored to align with your organization's goals and our mission.</p>
                        <p><a href="get-involved.html">Learn more about partnerships →</a></p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>Can I speak at the Summit?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We welcome applications from industry experts who would like to contribute to our Summit discussions. Please contact us with your background, expertise, and proposed topic for consideration.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>How do I access your year-round programs?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Our year-round capacity building programs include training labs, incubators, and mentorship opportunities. Information about these programs is shared with our community through our newsletter and social media channels.</p>
                        <p><a href="programs.html">View our programs →</a></p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>What is Fashion Nexus Ghana's mission?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Fashion Nexus Ghana is dedicated to scaling and developing Ghana's fashion industry through strategic convening, capacity building, policy engagement, and market development. We aim to strengthen the entire fashion ecosystem.</p>
                        <p><a href="about.html">Learn more about us →</a></p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h4>How can media organizations get involved?</h4>
                        <i data-lucide="chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Media organizations can participate as media partners, covering our events and initiatives. We also welcome journalists and content creators to apply for media accreditation to our Summit and other programs.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="contact-cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Join Ghana's Fashion Revolution?</h2>
                <p>Whether you're a designer, investor, policymaker, or fashion enthusiast, there's a place for you in building Ghana's fashion future.</p>
                <div class="cta-actions">
                    <a href="register.html" class="btn btn-primary">
                        <i data-lucide="calendar"></i>
                        Register for Summit
                    </a>
                    <a href="get-involved.html" class="btn btn-secondary">
                        <i data-lucide="handshake"></i>
                        Explore Partnerships
                    </a>
                </div>
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

    <script src="assets/js/main.js"></script>
    <script src="assets/js/contact.js"></script>
</body>
</html>