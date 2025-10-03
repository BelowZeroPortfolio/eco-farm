# IoT Farm Monitoring System

A comprehensive web-based IoT farm monitoring system built with PHP, featuring real-time sensor data visualization, pest detection, user management, and reporting capabilities.

## Features

### 🌱 **Dashboard**
- Real-time sensor data display (temperature, humidity, soil moisture)
- Live camera feeds with AI detection simulation
- System health monitoring
- Interactive charts and visualizations

### 👥 **User Management** (Admin Only)
- Complete CRUD operations for user accounts
- Role-based access control (Admin, Farmer, Student)
- User status management (Active/Inactive)
- Secure password management

### 🔒 **Profile Management**
- Tabbed interface for better organization
- Profile information editing
- Security settings and password changes
- User preferences and notifications
- Account overview with activity tracking

### 🐛 **Pest Detection**
- AI-powered pest detection simulation
- Real-time camera monitoring
- Alert management system
- Pest identification and treatment recommendations

### 📊 **Reports & Analytics**
- Comprehensive data visualization
- Export functionality (CSV, PDF)
- Date range filtering
- Sensor data analytics

### 📱 **Responsive Design**
- Modern, minimalist UI with Tailwind CSS
- Dark mode support
- Mobile-friendly responsive layout
- Consistent design system across all pages

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3 (Tailwind CSS), JavaScript
- **Charts**: Chart.js
- **Icons**: Font Awesome
- **PDF Generation**: Custom PDF library
- **Authentication**: Session-based with role management

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/iot-farm-monitoring-system.git
   cd iot-farm-monitoring-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create a new MySQL database
   - Import the schema: `database/schema.sql`
   - Import sample data: `database/sample_data.sql`

4. **Configure Database**
   - Copy `config/database.php.example` to `config/database.php`
   - Update database credentials in `config/database.php`

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   ```

6. **Access the Application**
   - Navigate to your web server URL
   - Default admin login: `admin` / `password123`

## Project Structure

```
├── config/              # Configuration files
├── database/           # Database schema and sample data
├── includes/           # Shared components and utilities
├── data/              # JSON data files
├── uploads/           # File upload directory
├── logs/              # Application logs
├── .kiro/             # Kiro IDE specifications
├── dashboard.php      # Main dashboard
├── user_management.php # User management (Admin)
├── profile.php        # User profile management
├── sensors.php        # Sensor data visualization
├── pest_detection.php # Pest detection system
├── reports.php        # Reports and analytics
├── camera_management.php # Camera system management
├── notifications.php  # Notification center
└── settings.php       # System settings
```

## User Roles

### 👑 **Admin**
- Full system access
- User management capabilities
- System configuration
- All reporting features

### 🌾 **Farmer**
- Dashboard access
- Sensor monitoring
- Pest detection alerts
- Basic reporting

### 🎓 **Student**
- Limited dashboard access
- Educational content
- Basic sensor data viewing

## Key Features Implemented

### ✅ **Completed Features**
- [x] User authentication and authorization
- [x] Role-based access control
- [x] Dashboard with real-time data simulation
- [x] User management system (CRUD operations)
- [x] Profile management with tabbed interface
- [x] Sensor data visualization
- [x] Pest detection simulation
- [x] Camera management system
- [x] Notification system
- [x] Reports with export functionality
- [x] Responsive design with dark mode
- [x] Settings management

### 🚧 **Future Enhancements**
- [ ] Real IoT device integration
- [ ] Advanced AI pest detection
- [ ] Mobile app development
- [ ] Advanced analytics and ML
- [ ] Multi-language support
- [ ] API development for third-party integrations

## Screenshots

*Add screenshots of your application here*

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions, please open an issue on GitHub or contact [your-email@example.com].

## Acknowledgments

- Built with modern web technologies
- Inspired by real-world IoT farming solutions
- Designed for educational and practical use cases

---

**Note**: This is a demonstration/educational project. For production use, ensure proper security measures, real IoT integrations, and thorough testing.