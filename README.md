# Automatic Data Backup System

A comprehensive PHP-based system for automating data backups with encryption, scheduling, and management features.

## Features

- **Automated Backups**: Schedule backups to run at specified intervals
- **Security**: AES-256 encryption for backup files
- **Management Interface**: Web-based dashboard to manage backup jobs and records
- **Monitoring**: Track backup status, size, and duration
- **Retention Policy**: Automatic cleanup of old backups based on retention settings
- **Compression**: Built-in compression to save storage space

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- tar command-line utility (for compression)

## Installation

1. Clone this repository to your web server's document root:

```bash
git clone https://github.com/yourusername/AutomaticDataBackup.git
```

2. Update the database configuration in `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
```

3. Run the database initialization script:

```bash
php init_db.php
```

4. Set up the scheduler by adding a cron job to run the scheduler script every hour:

```bash
# Edit crontab
crontab -e

# Add this line to run scheduler every hour
0 * * * * /usr/bin/php /path/to/your/AutomaticDataBackup/scheduler.php
```

5. Ensure proper file permissions for the backup directories:

```bash
chmod -R 755 backups/
chmod -R 755 logs/
chmod -R 755 temp/
```

## Security

- Change the default encryption key in `config/config.php`
- The default admin login is:
  - Username: `admin`
  - Password: `password123`
- Change the default password immediately after first login

## Usage

1. Access the system via your web browser
2. Log in with the default credentials (change immediately)
3. Create backup jobs in the Settings section
4. Monitor backup status from the dashboard
5. Download or delete backups from the Manage Backups section

## Configuration

The system can be configured through the Settings section in the web interface or by editing `config/config.php` directly.

## Backup Scheduling

The system supports the following schedule types:
- Manual: Run on demand
- Hourly: Run every hour
- Daily: Run once daily at specified time
- Weekly: Run once weekly on specified day
- Monthly: Run once monthly on specified date

## Troubleshooting

- Check logs in the `logs/` directory for error information
- Ensure the web server has write permissions to the `backups/`, `logs/`, and `temp/` directories
- Verify that the MySQL database credentials are correct

## License

This project is open source and available under the [MIT License](LICENSE).