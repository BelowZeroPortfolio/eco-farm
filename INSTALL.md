# Installation Guide

## Required Software

### 1. XAMPP
```
Download: https://www.apachefriends.org/
Install and start Apache + MySQL
```

### 2. Python 3.8+
```
Download: https://www.python.org/downloads/
✓ Check "Add Python to PATH" during installation
```

### 3. Composer
```
Download: https://getcomposer.org/download/
Run installer
```

## Install Python Packages

Open Command Prompt in project folder and run:

```bash
pip install flask ultralytics pillow opencv-python
```

## Install PHP Dependencies

```bash
composer install
```

## Setup Database

1. Start XAMPP (Apache + MySQL)
2. Open: http://localhost/phpmyadmin
3. Create database: `farm_database`
4. Import: `database/schema.sql`
5. Import: `database/sample_data.sql` (optional for sample data)



## Run Application

1. Start YOLO service: `start_yolo_service.bat`
2. Open browser: `http://localhost/eco-farm/login.php`
3. Login: admin / password 
or 
farmer / password 
Done!
