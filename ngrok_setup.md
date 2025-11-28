# ngrok Setup Guide for YOLO Pest Detection

This guide focuses on setting up **ngrok** to expose your local YOLO service to the internet so InfinityFree can access it.

## What is ngrok?

ngrok creates a secure tunnel from the internet to your laptop, allowing your InfinityFree website to communicate with the YOLO service running on your laptop.

```
InfinityFree Website → ngrok Tunnel → Your Laptop (YOLO Service)
```

## ngrok Installation

### Step 1: Download ngrok

**Option A - Using Windows Package Manager (Easiest):**
```bash
winget install ngrok.ngrok
```

**Option B - Manual Download:**
1. Go to: https://ngrok.com/download
2. Download the Windows version (ZIP file)
3. Extract `ngrok.exe` to a folder (e.g., `C:\ngrok\`)
4. Add the folder to your Windows PATH:
   - Search "Environment Variables" in Windows
   - Edit "Path" under System Variables
   - Add the folder path where ngrok.exe is located
   - Click OK and restart Command Prompt

### Step 2: Create ngrok Account (Required)

1. Go to: https://ngrok.com
2. Click "Sign up" (it's FREE)
3. Create an account (you can use Google/GitHub)
4. After login, you'll see your **authtoken** on the dashboard

### Step 3: Configure Your Authtoken

This connects ngrok on your laptop to your account:

```bash
ngrok config add-authtoken YOUR_AUTHTOKEN_HERE
```

**Example:**
```bash
ngrok config add-authtoken 2abc123XYZ_your_actual_token_here
```

**Where to find your authtoken:**
- Login to https://dashboard.ngrok.com
- Go to "Your Authtoken" section
- Copy the token (looks like: `2abc123XYZ...`)

### Step 4: Test ngrok

Run this command to test:
```bash
ngrok http 5000
```

You should see:
```
Session Status    online
Forwarding        https://abc123.ngrok-free.app -> http://localhost:5000
```

Press `Ctrl+C` to stop the test.

## How to Use ngrok with YOLO Service

### Starting the Services

**Option 1 - Automatic (Recommended):**
```bash
start_yolo_with_ngrok_debug.bat
```
This script automatically:
- Starts the Flask YOLO service on port 5000
- Starts ngrok tunnel
- Opens 2 windows (keep both open!)

**Option 2 - Manual:**
```bash
# Terminal 1: Start YOLO service
python yolo_detect2.py

# Terminal 2: Start ngrok
ngrok http 5000
```

### Getting Your ngrok URL

After starting ngrok, you'll see a window like this:
```
ngrok

Session Status    online
Account           your-email@example.com
Forwarding        https://abc123.ngrok-free.app -> http://localhost:5000
Forwarding        http://abc123.ngrok-free.app -> http://localhost:5000

Connections       ttl     opn     rt1
                  0       0       0.00
```

**Your ngrok URL is:** `abc123.ngrok-free.app` (the part after `https://`)

**Helper Script:**
```bash
get_ngrok_url.bat
```
This script helps extract the URL automatically.

### Updating Your Website Config

Every time you start ngrok, you get a **NEW URL** (on free tier). You need to update your website:

**Step 1:** Open `config/env.php`

**Step 2:** Find this line:
```php
'YOLO_SERVICE_HOST' => 'old-url.ngrok-free.app',
```

**Step 3:** Replace with your new ngrok URL (WITHOUT `https://`):
```php
'YOLO_SERVICE_HOST' => 'abc123.ngrok-free.app',
```

**Step 4:** Upload `config/env.php` to InfinityFree using FileZilla/FTP

**Step 5:** Test your website:
```
https://sagayecofarm.infinityfreeapp.com/pest_detection.php
```

## What Changes on Different Laptops?

### On Your Current Laptop:
- ngrok is already installed and configured
- Authtoken is saved
- Just run: `start_yolo_with_ngrok_debug.bat`

### On a New Laptop:
You need to:
1. **Install ngrok** (download or winget)
2. **Configure authtoken** (one-time setup):
   ```bash
   ngrok config add-authtoken YOUR_TOKEN
   ```
3. **Copy project files** (yolo_detect2.py, best.pt, batch files)
4. **Install Python packages**:
   ```bash
   pip install flask flask-cors ultralytics opencv-python pillow
   ```
5. **Run the startup script**:
   ```bash
   start_yolo_with_ngrok_debug.bat
   ```

### What Stays the Same:
- Your ngrok account and authtoken (use the same one)
- The project files (yolo_detect2.py, best.pt)
- The batch scripts (start_yolo_with_ngrok_debug.bat, etc.)

### What Changes:
- **ngrok URL changes EVERY TIME you restart** (free tier limitation)
- You must update `config/env.php` with the new URL each time
- Upload the updated file to InfinityFree

## ngrok Troubleshooting

### "ngrok not found" or "command not found"
**Problem:** Windows can't find ngrok.exe

**Solution:**
1. Check if ngrok is installed: `ngrok version`
2. If not installed, use: `winget install ngrok.ngrok`
3. Or download manually and add to PATH
4. Restart Command Prompt after installation

### "Failed to authenticate"
**Problem:** Authtoken not configured

**Solution:**
```bash
ngrok config add-authtoken YOUR_TOKEN_HERE
```
Get your token from: https://dashboard.ngrok.com

### "Session limit exceeded"
**Problem:** Free ngrok accounts have limits

**Solution:**
- Wait a few minutes and try again
- Or restart ngrok
- Free tier: 1 online ngrok process at a time

### "Tunnel not found" or "502 Bad Gateway"
**Problem:** YOLO service not running or ngrok pointing to wrong port

**Solution:**
1. Make sure Flask is running: `python yolo_detect2.py`
2. Check Flask is on port 5000
3. Then start ngrok: `ngrok http 5000`
4. Both must be running simultaneously

### ngrok URL keeps changing
**This is normal for free tier!**
- Free ngrok URLs change every restart
- You must update `config/env.php` each time
- Paid ngrok accounts get permanent URLs

## Important Notes

### Keep Windows Open
- **YOLO Service window** - Must stay open
- **ngrok Tunnel window** - Must stay open
- Closing either will stop the service

### URL Changes
- ngrok URL changes every time you restart (free tier)
- Always check the ngrok window for the current URL
- Update `config/env.php` with the new URL
- Upload to InfinityFree after updating

### Laptop Requirements
- Must stay ON while using the website
- Must be connected to internet
- Must have both services running

### Session Timeout
- Free ngrok sessions timeout after ~2 hours of inactivity
- Just restart the services if this happens

## Daily Workflow

**Morning/When Starting:**
1. Run: `start_yolo_with_ngrok_debug.bat`
2. Check ngrok window for URL (e.g., `abc123.ngrok-free.app`)
3. Open `config/env.php`
4. Update: `'YOLO_SERVICE_HOST' => 'abc123.ngrok-free.app',`
5. Upload `config/env.php` to InfinityFree via FTP
6. Test website: https://sagayecofarm.infinityfreeapp.com/pest_detection.php

**During Use:**
- Keep both service windows open
- Don't close or minimize them

**When Done:**
- Close the YOLO Service window
- Close the ngrok Tunnel window
- Or just close the main terminal

## Quick Reference

### Check if ngrok is installed:
```bash
ngrok version
```

### Configure authtoken (one-time):
```bash
ngrok config add-authtoken YOUR_TOKEN
```

### Start ngrok manually:
```bash
ngrok http 5000
```

### View ngrok dashboard:
```
http://127.0.0.1:4040
```

### Get your authtoken:
```
https://dashboard.ngrok.com
```
