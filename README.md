# CheatStore - Premium Gaming Cheats Platform

A modern, secure PHP-based platform for selling gaming cheats and hacks with license key management, similar to Sellix. Built with security, scalability, and user experience in mind.

## 🚀 Features

### Core Features
- **User Authentication & Registration** - Secure user accounts with password hashing
- **Product Management** - Add, edit, and manage cheat products with categories
- **License Key Generation** - Automatic generation of unique license keys
- **Payment Processing** - Stripe integration for secure payments
- **Order Management** - Complete order tracking and management
- **User Dashboard** - Personal dashboard with license keys and order history
- **Responsive Design** - Modern, mobile-friendly interface

### Security Features
- **CSRF Protection** - Cross-site request forgery protection
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output escaping
- **Password Hashing** - Argon2id password hashing
- **Session Security** - Secure session management
- **Input Validation** - Comprehensive form validation

### Technical Features
- **Nginx Compatible** - Optimized for nginx deployment
- **Database Optimization** - Indexed queries for performance
- **File Upload Security** - Secure file handling with validation
- **Email Integration** - Password reset and notification emails
- **Activity Logging** - Comprehensive user activity tracking

## 📋 Requirements

### Server Requirements
- **PHP** 8.0 or higher
- **MySQL** 5.7 or higher (or MariaDB 10.2+)
- **Nginx** or Apache web server
- **SSL Certificate** (recommended for production)

### PHP Extensions
- `pdo_mysql`
- `openssl`
- `mbstring`
- `json`
- `curl`
- `fileinfo`

## 🛠️ Installation

### 2. Set Up Database
```bash
# Create database
mysql -u root -p
CREATE DATABASE cheatstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cheatstore;

# Import schema
mysql -u root -p cheatstore < database/schema.sql
```

### 3. Configure Database Connection
Edit `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'cheatstore';
private $username = 'your_db_username';
private $password = 'your_db_password';
```

### 4. Configure Application Settings
Edit `config/config.php`:
```php
// Update these values
define('SITE_URL', 'https://yourdomain.com');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_stripe_key');
define('STRIPE_SECRET_KEY', 'sk_test_your_stripe_key');
define('JWT_SECRET', 'your_jwt_secret_key');
```

### 5. Set Up Stripe
1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Get your API keys from the Stripe Dashboard
3. Update the configuration with your keys
4. Set up webhook endpoints (optional)

### 6. Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/cheatstore;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security: Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(config|database|includes) {
        deny all;
    }
}
```

#### Apache Configuration (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?page=$1 [L,QSA]

# Security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "no-referrer-when-downgrade"

# Deny access to sensitive directories
<Files "config/*">
    Order allow,deny
    Deny from all
</Files>
```

### 7. Set File Permissions
```bash
chmod 755 /path/to/cheatstore
chmod 644 /path/to/cheatstore/.htaccess
chmod -R 755 /path/to/cheatstore/uploads
```

### 8. Create Admin User
The system creates a default admin user:
- **Username**: admin
- **Password**: admin123
- **Email**: admin@cheatstore

**Important**: Change the admin password immediately after installation!

## 🎯 Usage

### Adding Products
1. Log in as admin
2. Navigate to Products section
3. Add new product with:
   - Name and description
   - Category selection
   - Pricing for 1 day, 1 week, 1 month
   - Features list
   - Product image (optional)

### Managing Orders
- View all orders in the admin panel
- Track payment status
- Generate license keys automatically
- Handle refunds and cancellations

### User Experience
- Users can browse products by category
- Secure checkout with Stripe
- Instant license key delivery
- User dashboard with active keys
- Order history and support

## 🔧 Configuration

### Environment Variables
For production, consider using environment variables:
```bash
# Create .env file
DB_HOST=localhost
DB_NAME=cheatstore
DB_USER=your_username
DB_PASS=your_password
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
```

### Customization
- **Styling**: Edit `assets/css/style.css`
- **Templates**: Modify files in `pages/` directory
- **Functions**: Extend functionality in `includes/` directory
- **Database**: Add custom fields in database schema

## 🔒 Security Considerations

### Production Checklist
- [ ] Use HTTPS/SSL
- [ ] Change default admin credentials
- [ ] Set up proper file permissions
- [ ] Configure firewall rules
- [ ] Enable error logging
- [ ] Set up backup system
- [ ] Use strong database passwords
- [ ] Configure rate limiting
- [ ] Set up monitoring

### Security Features
- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Prepared statements used throughout
- **XSS Protection**: Output escaping implemented
- **CSRF Protection**: Token-based protection for forms
- **Session Security**: Secure session configuration
- **File Upload Security**: Strict file type and size validation

## 📊 Database Schema

### Key Tables
- **users**: User accounts and authentication
- **products**: Product information and pricing
- **categories**: Product categories
- **orders**: Order tracking and payment info
- **license_keys**: Generated license keys
- **activity_logs**: User activity tracking

### Relationships
- Products belong to categories
- Orders belong to users and products
- License keys belong to orders, users, and products
- Activity logs track user actions

## 🚀 Deployment

### Production Deployment
1. **Set up server** with required software
2. **Configure web server** (Nginx/Apache)
3. **Set up SSL certificate**
4. **Import database schema**
5. **Configure application settings**
6. **Set file permissions**
7. **Test all functionality**
8. **Set up monitoring and backups**

### Docker Deployment (Optional)
```dockerfile
FROM php:8.0-fpm
# Add Docker configuration here
```

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

#### Payment Processing Issues
- Verify Stripe API keys are correct
- Check Stripe account status
- Ensure webhook endpoints are configured (if using)

#### File Upload Issues
- Check file permissions on uploads directory
- Verify PHP upload settings in php.ini
- Check file size limits

#### Performance Issues
- Enable MySQL query caching
- Optimize database indexes
- Use CDN for static assets
- Enable PHP OPcache

## 📈 Performance Optimization

### Database Optimization
- Add indexes for frequently queried columns
- Use database connection pooling
- Implement query caching
- Regular database maintenance

### Frontend Optimization
- Minify CSS and JavaScript
- Optimize images
- Use CDN for external resources
- Enable browser caching

### Server Optimization
- Enable PHP OPcache
- Use Redis for session storage
- Configure proper caching headers
- Monitor server resources

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- **Email**: support@cheatstore
- **Discord**: CheatStore#1234
- **Documentation**: [Wiki](https://github.com/yourusername/cheatstore/wiki)

## 🔄 Updates

### Version History
- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added Stripe payment integration
- **v1.2.0** - Enhanced security features
- **v1.3.0** - Improved user dashboard

### Upcoming Features
- Multi-language support
- Advanced analytics
- API endpoints
- Mobile app
- Advanced admin panel

---

**Note**: This software is for educational purposes. Users are responsible for complying with local laws and game terms of service.
