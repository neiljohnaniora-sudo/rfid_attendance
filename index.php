<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Attendance System - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="icon-192.png">
    <script>
      // I-register ang Service Worker
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Service worker registered', reg))
            .catch(err => console.log('Service worker not registered', err));
        });
      }
    </script>
    <style>
        /* --- Base Styles --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        
        body { 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(-45deg, #1e3a8a, #3b82f6, #8b5cf6, #0f172a);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow-x: hidden;
        }

        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .container { 
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            position: relative; 
            overflow: hidden; 
            width: 850px; 
            max-width: 100%; 
            min-height: 650px; 
        }

        /* --- Form Containers --- */
        .form-container { 
            position: absolute; 
            top: 0; 
            height: 100%; 
            transition: all 0.6s ease-in-out; 
        }
        
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); }
        
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container.right-panel-active .sign-up-container { 
            transform: translateX(100%); 
            opacity: 1; 
            z-index: 5; 
            animation: show 0.6s; 
        }

        @keyframes show {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        form { 
            display: flex; flex-direction: column; padding: 0 40px; height: 100%; 
            justify-content: center; background-color: transparent;
        }
        
        .form-header { text-align: center; margin-bottom: 20px; }
        h2 { color: #ffffff; font-size: 24px; font-weight: 700; }
        
        .logo-text { font-size: 20px; font-weight: 800; color: #ffffff; margin-bottom: 15px; text-align: center; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .logo-text span { color: #e2e8f0; font-weight: 500; font-size: 10px; display: block; letter-spacing: 1px; text-shadow: none; }

        /* --- Inputs --- */
        .input-group { margin-bottom: 10px; width: 100%; text-align: left; }
        .input-group label { display: block; font-size: 12px; font-weight: 600; color: #e2e8f0; margin-bottom: 4px; }
        
        input, select { 
            background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff;
            padding: 10px 12px; width: 100%; border-radius: 8px; outline: none; font-size: 13px; transition: 0.2s;
        }
        input:focus, select:focus { background: rgba(255, 255, 255, 0.2); border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3); }
        select option { background: #1e3a8a; color: #ffffff; }
        input:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px #1e40af inset !important; -webkit-text-fill-color: #ffffff !important; }

        button.btn-main { 
            border-radius: 8px; border: none; background: #3b82f6; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            color: #ffffff; padding: 12px; cursor: pointer; margin-top: 15px; font-weight: 600; 
            width: 100%; transition: 0.3s; font-size: 14px;
        }
        button.btn-main:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6); }

        /* --- Overlay Section --- */
        .overlay-container { 
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%; 
            overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; 
        }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }
        
        .overlay { 
            background: rgba(15, 23, 42, 0.3);
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            color: #ffffff; position: relative; left: -100%; height: 100%; width: 200%; 
            transform: translateX(0); transition: transform 0.6s ease-in-out; 
        }
        .container.right-panel-active .overlay { transform: translateX(50%); }
        
        .overlay-panel { 
            position: absolute; display: flex; align-items: center; justify-content: center; 
            flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%; 
            transition: transform 0.6s ease-in-out; 
        }
        
        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }

        .overlay h1 { font-size: 28px; font-weight: 800; margin-bottom: 15px; }
        .overlay p { font-size: 14px; line-height: 1.6; color: #f8fafc; margin-bottom: 25px; }

        button.ghost { 
            background: transparent; border: 2px solid #ffffff; color: #ffffff;
            border-radius: 8px; padding: 10px 30px; font-weight: 600; cursor: pointer; transition: 0.3s;
        }
        button.ghost:hover { background: #ffffff; color: #1e3a8a; }

        /* --- Mobile Responsive --- */
        .mobile-switch { display: none; color: #93c5fd; font-weight: 600; margin-top: 15px; cursor: pointer; text-align: center; font-size: 13px; text-shadow: 0 1px 2px rgba(0,0,0,0.5); }
        
        @media (max-width: 768px) {
            .container { min-height: 700px; width: 95%; }
            .overlay-container { display: none; }
            .form-container { width: 100%; }
            .sign-in-container { width: 100%; }
            .sign-up-container { width: 100%; }
            .container.right-panel-active .sign-in-container { transform: none; opacity: 0; }
            .container.right-panel-active .sign-up-container { transform: none; opacity: 1; }
            .mobile-switch { display: block; }
            .sign-in-container { z-index: 5; transition: opacity 0.3s; }
            .sign-up-container { pointer-events: none; transition: opacity 0.3s; }
            .container.right-panel-active .sign-in-container { z-index: 1; pointer-events: none; }
            .container.right-panel-active .sign-up-container { pointer-events: auto; }
        }

        /* --- Loading Screen Styles --- */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            z-index: 99999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        .loader-content { text-align: center; color: #ffffff; padding: 20px; }

        .loader-logo { font-size: 28px; font-weight: 800; letter-spacing: 2px; margin-bottom: 25px; animation: pulse 1.5s infinite alternate; }
        .loader-logo span { display: block; font-size: 12px; font-weight: 500; color: #94a3b8; letter-spacing: 4px; margin-top: 8px; }

        .spinner {
            width: 50px; height: 50px; border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid #3b82f6; border-radius: 50%; margin: 0 auto;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.8; } 100% { transform: scale(1.05); opacity: 1; } }

        body.loading { overflow: hidden; }

        @media (max-width: 480px) {
            .loader-logo { font-size: 22px; }
            .loader-logo span { font-size: 10px; letter-spacing: 2px; }
            .spinner { width: 40px; height: 40px; }
        }
    </style>
</head>
<body class="loading">

    <!-- Loading Screen Overlay -->
    <div id="loading-screen">
        <div class="loader-content">
            <div class="loader-logo">ATTENDANCE<span>RFID MANAGEMENT SYSTEM</span></div>
            <div class="spinner"></div>
        </div>
    </div>

    <div class="container" id="container">
        
        <div class="form-container sign-up-container">
            <form action="register.php" method="POST" autocomplete="off">
                <div class="form-header">
                    <h2>Create Account</h2>
                </div>
                
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required />
                </div>
                <div class="input-group">
                    <label>Institutional Email (Optional)</label>
                    <input type="email" name="institutional_email" />
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required />
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required />
                </div>
                <div class="input-group">
                    <label>Home Address</label>
                    <input type="text" name="address" required />
                </div>
                <div class="input-group">
                    <label>Assign Grade Level</label>
                    <select name="assigned_grade" required>
                        <option value="" disabled selected>Select Grade Level</option>
                        <option value="Grade 1">Grade 1 Teacher</option>
                        <option value="Grade 2">Grade 2 Teacher</option>
                        <option value="Grade 3">Grade 3 Teacher</option>
                        <option value="Grade 4">Grade 4 Teacher</option>
                        <option value="Grade 5">Grade 5 Teacher</option>
                        <option value="Grade 6">Grade 6 Teacher</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required />
                </div>

                <button type="submit" class="btn-main">Register Now</button>
                <p class="mobile-switch" id="goSignInMobile">Already have an account? Sign In</p>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="login.php" method="POST" autocomplete="off">
                <div class="logo-text">ATTENDANCE<span>RFID MANAGEMENT SYSTEM</span></div>
                <div class="form-header">
                    <h2>LOGIN</h2>
                </div>
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required />
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required />
                </div>
                
                <button type="submit" class="btn-main">Sign In</button>
                <p class="mobile-switch" id="goSignUpMobile">Don't have an account? Register Here</p>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To stay connected, please login with your account.</p>
                    <button class="ghost" id="signIn">Sign In Here</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Teacher!</h1>
                    <p>Start your journey with our RFID Attendance System today.</p>
                    <button class="ghost" id="signUp">Create Account</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');
        const signUpMobile = document.getElementById('goSignUpMobile');
        const signInMobile = document.getElementById('goSignInMobile');

        signUpBtn.addEventListener('click', () => container.classList.add("right-panel-active"));
        signInBtn.addEventListener('click', () => container.classList.remove("right-panel-active"));
        
        // Mobile Support
        signUpMobile.addEventListener('click', () => container.classList.add("right-panel-active"));
        signInMobile.addEventListener('click', () => container.classList.remove("right-panel-active"));

        // Loading Screen Logic
        window.addEventListener('load', function() {
            // Set delay (e.g., 1.5 seconds) aron makita sa user ang loading animation
            setTimeout(function() {
                const loader = document.getElementById('loading-screen');
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                document.body.classList.remove('loading');
            }, 1500);
        });
    </script>

    <?php include 'chat_widget.php'; ?>


</body>
</html>