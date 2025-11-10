# Tool-Kart - Online Tool Rental System

Tool-Kart is a comprehensive online platform for renting tools and equipment. This system allows customers to browse, rent, and manage tool rentals while providing administrators with tools to manage inventory, users, and rentals.

## Features

### Customer Features
- User registration and authentication
- Browse tools by category
- View tool details with images and descriptions
- Add tools to cart
- Checkout with rental period selection
- View rental history and status
- Cancel active rentals
- Upload ID proof for rentals
- Receive notifications about rentals
- Review and rate rented tools

### Admin Features
- Dashboard with analytics and statistics
- Manage tools (add, edit, delete)
- Manage categories
- Manage users
- Manage administrators
- Manage rentals (view, update status, apply fines)
- Generate reports
- Manage FAQ section
- Configure system settings (late fees, damage fees)

## Technology Stack
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Backend**: PHP
- **Database**: MySQL
- **Server**: Apache (XAMPP)

## Installation

1. Clone or download the repository to your local machine
2. Copy the project files to your web server directory (e.g., htdocs in XAMPP)
3. Import the `tool_kart.sql` file into your MySQL database
4. Update the database connection settings in `includes/db_connect.php` if needed
5. Start your Apache and MySQL servers
6. Access the application through your web browser

## Database Configuration

The database schema is defined in `tool_kart.sql`. Import this file into your MySQL database to set up all required tables and initial data.

## Project Structure

```
├── admin/              # Admin panel files
├── assets/             # CSS, JavaScript, and image files
├── includes/           # Common PHP files (database connection, headers, etc.)
├── modules/            # Reusable PHP modules
├── uploads/            # User uploaded files (ID proofs, tool images)
├── *.php               # Main application pages
└── tool_kart.sql       # Database schema and initial data
```

## Key Components

### User Management
- Registration and login system
- Role-based access control (customer/admin)
- User profile management

### Tool Management
- Tool browsing by category
- Detailed tool information
- Availability tracking
- Image management

### Rental System
- Rental period selection
- Cart functionality
- Rental status tracking (active, returned, overdue, cancelled)
- Late fee and damage fee calculation
- ID proof verification

### Admin Panel
- Dashboard with analytics
- CRUD operations for tools, categories, users
- Rental management
- Report generation
- Settings configuration

## Security Features
- Password hashing
- Session management
- SQL injection prevention
- File upload validation
- Role-based access control

## Customization

You can customize the system by:
- Modifying the appearance in `assets/css/style.css`
- Updating settings in the admin panel
- Adding new categories and tools
- Extending functionality through modules

## Admin Login
- username : admin
- password : admin123

## License

This project is for educational purposes. Feel free to modify and extend it according to your needs.

## Support

For issues and feature requests, please create an issue in the repository.
