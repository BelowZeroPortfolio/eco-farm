<?php
/**
 * Forgot Password Page
 * Allows users to request a password reset link
 */

session_start();
require_once 'config/database.php'; // This loads config/env.php automatically
require_once 'logic/forgot_password_logic.php';

$forgotLogic = new ForgotPasswordLogic();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $forgotLogic->processRequest();
}

$error = $forgotLogic->getError();
$message = $forgotLogic->getMessage();
$email = $_POST['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - IoT Farm Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Animated Background Blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.7;
            animation: blob 20s infinite;
        }

        .blob-1 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            top: -10%;
            left: -10%;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            bottom: -10%;
            right: -10%;
            animation-delay: 4s;
        }

        .blob-3 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 8s;
        }

        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(20px, -50px) scale(1.1);
            }
            50% {
                transform: translate(-20px, 20px) scale(0.9);
            }
            75% {
                transform: translate(50px, 50px) scale(1.05);
            }
        }

        .dark .blob {
            opacity: 0.4;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-card {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-group input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .dark .input-group input:focus {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    <!-- Animated Background Blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="max-w-md w-full">
            <div class="glass-card rounded-2xl shadow-2xl p-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <a href="login.php" class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-arrow-left mr-1 text-xs"></i>
                        Back to Login
                    </a>
                    <button id="theme-toggle" onclick="toggleTheme()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-moon text-sm"></i>
                    </button>
                </div>

                <!-- Icon -->
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-key text-white text-lg"></i>
                        </div>
                    </div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Forgot Password?</h1>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Enter your email to receive a reset link</p>
                </div>

                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mr-2 text-sm"></i>
                            <span class="text-red-700 dark:text-red-300 text-xs"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 dark:text-green-400 mr-2 text-sm"></i>
                            <span class="text-green-700 dark:text-green-300 text-xs"><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" id="forgot-form" class="space-y-4">
                    <div class="input-group">
                        <label for="email" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                        <div class="relative">
                            <input type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($email); ?>"
                                placeholder="Enter your email"
                                required
                                class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none disabled:hover:translate-y-0">
                        <i class="fas fa-paper-plane mr-1.5 text-xs"></i>
                        <span id="btn-text">Send Reset Link</span>
                    </button>
                </form>

                <!-- Info -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        You'll receive an email with instructions to reset your password
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme functionality
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme === 'auto' ? (prefersDark ? 'dark' : 'light') : savedTheme;

            document.documentElement.classList.toggle('dark', theme === 'dark');
            updateThemeToggleIcon(theme);
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';

            document.documentElement.classList.toggle('dark', newTheme === 'dark');
            localStorage.setItem('theme', newTheme);
            updateThemeToggleIcon(newTheme);
        }

        function updateThemeToggleIcon(theme) {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.className = theme === 'dark' ? 'fas fa-sun text-sm' : 'fas fa-moon text-sm';
                }
            }
        }

        // Cooldown functionality
        function startCooldown(seconds) {
            const submitBtn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const btnIcon = submitBtn.querySelector('i');
            
            submitBtn.disabled = true;
            btnIcon.className = 'fas fa-clock mr-1.5 text-xs';
            
            let remaining = seconds;
            
            const countdown = setInterval(() => {
                btnText.textContent = `Wait ${remaining}s`;
                remaining--;
                
                if (remaining < 0) {
                    clearInterval(countdown);
                    submitBtn.disabled = false;
                    btnIcon.className = 'fas fa-paper-plane mr-1.5 text-xs';
                    btnText.textContent = 'Send Reset Link';
                }
            }, 1000);
        }

        // Check for cooldown error and start countdown
        <?php if ($error && preg_match('/wait (\d+) seconds/', $error, $matches)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            startCooldown(<?php echo $matches[1]; ?>);
        });
        <?php endif; ?>

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
        });

        // Initialize EmailJS with config from .env
        (function() {
            emailjs.init('<?php echo Env::get('EMAILJS_PUBLIC_KEY', 'YOUR_PUBLIC_KEY'); ?>');
        })();

        <?php if ($message && isset($_SESSION['reset_email_data'])): ?>
        // Send email using EmailJS
        (function() {
            const emailData = <?php echo json_encode($_SESSION['reset_email_data']); ?>;
            
            // Show sending status
            const alertDiv = document.querySelector('.bg-green-50');
            if (alertDiv) {
                const span = alertDiv.querySelector('span');
                span.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Sending email...';
            }
            
            // Template parameters - adjust these to match your EmailJS template
            const templateParams = {
                to_email: emailData.to_email,
                to_name: emailData.to_name,
                reset_link: emailData.reset_link,
                from_name: 'IoT Farm Monitoring System',
                // Add these common template variables
                user_email: emailData.to_email,
                username: emailData.to_name,
                link: emailData.reset_link,
                message: 'Click the link below to reset your password. This link will expire in 1 hour.'
            };
            
            console.log('Sending email with params:', templateParams);
            
            emailjs.send('<?php echo Env::get('EMAILJS_SERVICE_ID', 'YOUR_SERVICE_ID'); ?>', '<?php echo Env::get('EMAILJS_TEMPLATE_ID', 'YOUR_TEMPLATE_ID'); ?>', templateParams)
                .then(function(response) {
                    console.log('✓ Email sent successfully!', response.status, response.text);
                    if (alertDiv) {
                        const span = alertDiv.querySelector('span');
                        span.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Reset link sent! Check your email.';
                    }
                }, function(error) {
                    console.error('✗ Failed to send email:', error);
                    
                    let errorMessage = 'Email failed to send. ';
                    let helpText = '';
                    
                    // Specific error messages
                    if (error.text && error.text.includes('recipients address is empty')) {
                        errorMessage = '⚠️ EmailJS Configuration Error';
                        helpText = '<br><strong>Fix:</strong> Go to EmailJS Dashboard → Templates → <?php echo Env::get('EMAILJS_TEMPLATE_ID', 'YOUR_TEMPLATE_ID'); ?> → Settings tab → Set "To Email" field to: <code class="bg-yellow-200 px-1 rounded">{{to_email}}</code><br>See FIX_422_ERROR.md for detailed instructions.';
                    } else if (error.status === 422) {
                        errorMessage = 'Template configuration error (422)';
                        helpText = '<br>Check EmailJS template settings. See FIX_422_ERROR.md';
                    } else if (error.status === 401) {
                        errorMessage = 'Invalid EmailJS credentials (401)';
                        helpText = '<br>Check Public Key, Service ID, and Template ID';
                    }
                    
                    if (alertDiv) {
                        alertDiv.className = 'mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4 rounded-lg';
                        const icon = alertDiv.querySelector('i');
                        icon.className = 'fas fa-exclamation-triangle text-yellow-500 dark:text-yellow-400 mr-2 text-sm';
                        const span = alertDiv.querySelector('span');
                        span.className = 'text-yellow-700 dark:text-yellow-300 text-xs';
                        span.innerHTML = errorMessage + helpText + '<br><br><strong>Your reset link:</strong><br><a href="' + emailData.reset_link + '" class="text-blue-600 underline break-all">' + emailData.reset_link + '</a><br><small class="text-gray-600">Copy this link to reset your password manually</small>';
                    }
                });
            
            <?php unset($_SESSION['reset_email_data']); ?>
        })();
        <?php endif; ?>
    </script>
</body>
</html>
