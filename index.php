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
            min-height: 100vh; background-color: #f1f5f9; 
            overflow-x: hidden;
        }

        .container { 
            background: #ffffff; 
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
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
            justify-content: center; background-color: #ffffff;
        }
        
        .form-header { text-align: center; margin-bottom: 20px; }
        h2 { color: #0f172a; font-size: 24px; font-weight: 700; }
        
        .logo-text { font-size: 20px; font-weight: 800; color: #1e3a8a; margin-bottom: 15px; text-align: center; }
        .logo-text span { color: #64748b; font-weight: 500; font-size: 10px; display: block; letter-spacing: 1px; }

        /* --- Inputs --- */
        .input-group { margin-bottom: 10px; width: 100%; text-align: left; }
        .input-group label { display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
        
        input, select { 
            background: #f8fafc; border: 1px solid #cbd5e1; color: #0f172a;
            padding: 10px 12px; width: 100%; border-radius: 8px; outline: none; font-size: 13px; transition: 0.2s;
        }
        input:focus, select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        button.btn-main { 
            border-radius: 8px; border: none; background: #0f172a; 
            color: #ffffff; padding: 12px; cursor: pointer; margin-top: 15px; font-weight: 600; 
            width: 100%; transition: 0.3s; font-size: 14px;
        }
        button.btn-main:hover { background: #1e3a8a; transform: translateY(-1px); }

        /* --- Overlay Section --- */
        .overlay-container { 
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%; 
            overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; 
        }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }
        
        .overlay { 
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
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
        .overlay p { font-size: 14px; line-height: 1.6; color: #e2e8f0; margin-bottom: 25px; }

        button.ghost { 
            background: transparent; border: 2px solid #ffffff; color: #ffffff;
            border-radius: 8px; padding: 10px 30px; font-weight: 600; cursor: pointer; transition: 0.3s;
        }
        button.ghost:hover { background: #ffffff; color: #0f172a; }

        /* --- Mobile Responsive --- */
        .mobile-switch { display: none; color: #1e3a8a; font-weight: 600; margin-top: 15px; cursor: pointer; text-align: center; font-size: 13px; }
        
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
    </style>
</head>
<body>

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
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="example@email.com" required />
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="09xxxxxxxxx" required />
                </div>
                <div class="input-group">
                    <label>Home Address</label>
                    <input type="text" name="address" placeholder="City, Province" required />
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
                    <input type="password" name="password" placeholder="••••••••" required />
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
                    <input type="email" name="email" placeholder="example@email.com" required />
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required />
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
    </script>

    <?php include 'chat_widget.php'; ?>


</body>
</html>