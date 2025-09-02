const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const pool = require('../config/database');
const { validationResult } = require('express-validator');

const generateToken = (userId, role) => {
    return jwt.sign(
        { userId, role },
        process.env.JWT_SECRET,
        { expiresIn: process.env.JWT_EXPIRE || '7d' }
    );
};

const register = async (req, res) => {
    try {
        const errors = validationResult(req);
        if (!errors.isEmpty()) {
            return res.status(400).json({ 
                success: false, 
                errors: errors.array() 
            });
        }
        
        const { username, email, password, fullName, adminKey } = req.body;
        
        // Check admin registration key
        if (adminKey !== process.env.ADMIN_REGISTER_KEY) {
            return res.status(403).json({ 
                success: false, 
                message: 'Invalid admin registration key' 
            });
        }
        
        // Check if user exists
        const [existing] = await pool.execute(
            'SELECT * FROM admin_users WHERE email = ? OR username = ?',
            [email, username]
        );
        
        if (existing.length > 0) {
            return res.status(400).json({ 
                success: false, 
                message: 'User already exists' 
            });
        }
        
        // Hash password
        const hashedPassword = await bcrypt.hash(password, 12);
        
        // Create user
        const [result] = await pool.execute(
            'INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)',
            [username, email, hashedPassword, fullName, 'admin']
        );
        
        const token = generateToken(result.insertId, 'admin');
        
        res.status(201).json({
            success: true,
            message: 'Admin account created successfully',
            token,
            user: {
                id: result.insertId,
                username,
                email,
                fullName,
                role: 'admin'
            }
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ 
            success: false, 
            message: 'Error creating account' 
        });
    }
};

const login = async (req, res) => {
    try {
        const { email, password } = req.body;
        
        // Get user
        const [users] = await pool.execute(
            'SELECT * FROM admin_users WHERE email = ? AND is_active = true',
            [email]
        );
        
        if (users.length === 0) {
            return res.status(401).json({ 
                success: false, 
                message: 'Invalid credentials' 
            });
        }
        
        const user = users[0];
        
        // Check password
        const isValid = await bcrypt.compare(password, user.password_hash);
        
        if (!isValid) {
            return res.status(401).json({ 
                success: false, 
                message: 'Invalid credentials' 
            });
        }
        
        // Update last login
        await pool.execute(
            'UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?',
            [user.admin_id]
        );
        
        const token = generateToken(user.admin_id, user.role);
        
        res.json({
            success: true,
            message: 'Login successful',
            token,
            user: {
                id: user.admin_id,
                username: user.username,
                email: user.email,
                fullName: user.full_name,
                role: user.role
            }
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ 
            success: false, 
            message: 'Error during login' 
        });
    }
};

module.exports = { register, login };