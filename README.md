# Corporate Setup Limited - Internal CRM

A comprehensive Customer Relationship Management (CRM) system designed exclusively for Corporate Setup Limited's internal staff operations.

## Features

### Core Modules
- **Dashboard** - Executive overview with key metrics and quick actions
- **Company Management** - Complete client company profiles and contacts
- **Registration Management** - Track all regulatory and corporate registrations
- **Compliance Tracker** - Monitor expiring registrations and compliance status
- **Task Management** - Daily, weekly, and monthly task tracking
- **Document Management** - Upload, organize, and track documents
- **Daily Activity Reports** - Staff daily reporting and approval workflow
- **Communications Log** - Track all client interactions
- **Meeting Management** - Schedule and document meetings
- **Opportunities** - Track business opportunities and tenders
- **Elite Members** - Manage premium client subscriptions
- **Staff Management** - Employee directory and permissions
- **Calendar** - Unified view of deadlines, meetings, and events
- **Global Search** - Search across all data
- **Reports** - Generate business reports with export options

### Security Features
- Secure authentication with password hashing (Argon2ID)
- Role-based access control (RBAC)
- CSRF protection on all forms
- XSS prevention
- Input validation and sanitization
- Session timeout and management
- Activity logging and audit trails

## Technology Stack

- **Backend**: PHP 8+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Charts**: Chart.js
- **Icons**: Font Awesome 6

## System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- PDO PHP extension
- JSON PHP extension

## Installation

### 1. Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE corporate_setup_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p corporate_setup_crm < crm.sql
```

3. Import the seed data (optional):
```bash
mysql -u root -p corporate_setup_crm < seed.sql
```

### 2. Configuration

1. Open `config/database.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'corporate_setup_crm');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Ensure the `uploads/` directory is writable:
```bash
chmod -R 755 uploads/
```

### 3. Web Server Configuration

#### Apache (.htaccess)
Place the following in your root `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/crm;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. First Login

After installation, use the default credentials:

**Email**: `admin@corporatesetup.com`  
**Password**: `Admin@123`

You will be prompted to change this password on first login.

## User Roles

| Role | Description |
|------|-------------|
| Administrator | Full system access |
| Compliance | Manage registrations and compliance |
| Business Development | Client relationships and opportunities |
| Operations | Daily operations and tasks |
| Legal | Legal documents and contracts |
| IT | System configuration and users |
| Marketing | Marketing and communications |
| Management | Executive oversight and approvals |

## Directory Structure

```
crm/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   ├── config.php
│   └── database.php
├── helpers/
│   ├── security.php
│   ├── validators.php
│   └── formatters.php
├── includes/
│   ├── auth.php
│   └── functions.php
├── uploads/
│   ├── documents/
│   ├── certificates/
│   └── profiles/
├── views/
│   ├── layouts/
│   ├── companies/
│   ├── registrations/
│   ├── tasks/
│   └── ... (other modules)
├── crm.sql          # Database schema
├── seed.sql         # Sample data
├── index.php        # Dashboard
├── login.php
├── companies.php
├── registrations.php
├── tasks.php
└── ... (other pages)
```

## Customization

### Adding New Registration Types

Navigate to Settings > Registration Types or insert directly into the database:

```sql
INSERT INTO registration_types (name, category, description, processing_time, validity_period, cost, renewable, status)
VALUES ('Your Registration', 'regulatory', 'Description', 30, 365, 50000, 1, 'active');
```

### Adding New Industries

```sql
INSERT INTO industries (name, description, status)
VALUES ('New Industry', 'Description', 'active');
```

### Changing Colors

Edit the CSS variables in `assets/css/style.css`:

```css
:root {
    --primary: #0f2942;      /* Navy Blue */
    --secondary: #c9a227;    /* Gold */
    --success: #059669;      /* Green */
    /* ... */
}
```

## Backup

### Manual Backup

Run the following command to create a database backup:

```bash
mysqldump -u root -p corporate_setup_crm > backup_$(date +%Y%m%d).sql
```

### Automated Backup (Cron)

Add to crontab for daily backups at midnight:

```bash
0 0 * * * mysqldump -u username -ppassword corporate_setup_crm > /backups/crm_$(date +\%Y\%m\%d).sql
```

## Troubleshooting

### Database Connection Error

1. Verify database credentials in `config/database.php`
2. Ensure MySQL service is running
3. Check that the user has permissions on the database

### File Upload Issues

1. Check `uploads/` directory permissions
2. Verify `upload_max_filesize` and `post_max_size` in php.ini
3. Check file type restrictions in `config/config.php`

### Session Issues

1. Ensure sessions are enabled in PHP
2. Check `session.save_path` is writable
3. Clear browser cookies and cache

## Support

For issues or feature requests, contact the IT department at IT@corporatesetup.com

## License

This software is proprietary to Corporate Setup Limited. Unauthorized use, distribution, or modification is strictly prohibited.

---

**Corporate Setup Limited**  
*Your Trusted Business Partner*  
Copyright &copy; <?= date('Y') ?>
