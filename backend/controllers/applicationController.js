const pool = require('../config/database');
const nodemailer = require('nodemailer');

// Email transporter
const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: process.env.SMTP_PORT,
    secure: false,
    auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS
    }
});

const submitApplication = async (req, res) => {
    try {
        const {
            firstName, lastName, email, phone,
            organization, jobTitle, country,
            registrationType, dietaryRestrictions,
            specialRequirements, howHeard
        } = req.body;
        
        // Check if already registered
        const [existing] = await pool.execute(
            'SELECT * FROM registrations WHERE email = ?',
            [email]
        );
        
        if (existing.length > 0) {
            return res.status(400).json({
                success: false,
                message: 'This email is already registered'
            });
        }
        
        // Insert registration
        const [result] = await pool.execute(
            `INSERT INTO registrations 
            (first_name, last_name, email, phone, organization, job_title, 
             country, registration_type, dietary_restrictions, 
             special_requirements, how_heard) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [firstName, lastName, email, phone, organization, jobTitle,
             country || 'Ghana', registrationType || 'general', 
             dietaryRestrictions, specialRequirements, howHeard]
        );
        
        // Send confirmation email
        const mailOptions = {
            from: process.env.SMTP_USER,
            to: email,
            subject: 'Registration Received - Business of Ghanaian Fashion Summit 2025',
            html: `
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto;">
                    <div style="background: linear-gradient(135deg, #CE1126, #FCD116, #006B3F); height: 5px;"></div>
                    <div style="padding: 20px;">
                        <h2 style="color: #333;">Thank You for Registering!</h2>
                        <p>Dear ${firstName} ${lastName},</p>
                        <p>We have received your registration for the Business of Ghanaian Fashion Summit 2025.</p>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            <h3 style="color: #CE1126;">Event Details</h3>
                            <p><strong>Date:</strong> Thursday, October 16, 2025</p>
                            <p><strong>Venue:</strong> Kempinski Hotel Gold Coast City, Accra</p>
                            <p><strong>Registration Type:</strong> ${registrationType}</p>
                        </div>
                        <p>Your application is currently under review. We will contact you within 48-72 hours with further details about payment and confirmation.</p>
                        <p>If you have any questions, please contact us at info@fashionnexusghana.com</p>
                        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                        <p style="color: #666; font-size: 12px;">Fashion Nexus Ghana | Building a sustainable future for Ghana's fashion industry</p>
                    </div>
                </div>
            `
        };
        
        await transporter.sendMail(mailOptions);
        
        // Emit real-time update to admin dashboard
        const io = req.app.get('io');
        io.to('admin-room').emit('new-application', {
            id: result.insertId,
            name: `${firstName} ${lastName}`,
            email,
            organization,
            registrationType,
            timestamp: new Date()
        });
        
        res.status(201).json({
            success: true,
            message: 'Registration successful! Please check your email for confirmation.',
            registrationId: result.insertId
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({
            success: false,
            message: 'Error processing registration'
        });
    }
};

const getApplications = async (req, res) => {
    try {
        const { status, type, search, page = 1, limit = 20 } = req.query;
        const offset = (page - 1) * limit;
        
        let query = 'SELECT * FROM registrations WHERE 1=1';
        const params = [];
        
        if (status) {
            query += ' AND registration_status = ?';
            params.push(status);
        }
        
        if (type) {
            query += ' AND registration_type = ?';
            params.push(type);
        }
        
        if (search) {
            query += ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR organization LIKE ?)';
            const searchTerm = `%${search}%`;
            params.push(searchTerm, searchTerm, searchTerm, searchTerm);
        }
        
        // Get total count
        const [countResult] = await pool.execute(
            query.replace('SELECT *', 'SELECT COUNT(*) as total'),
            params
        );
        
        // Get paginated results
        query += ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const [applications] = await pool.execute(query, params);
        
        res.json({
            success: true,
            data: applications,
            pagination: {
                total: countResult[0].total,
                page: parseInt(page),
                limit: parseInt(limit),
                pages: Math.ceil(countResult[0].total / limit)
            }
        });
    } catch (error) {
        console.error