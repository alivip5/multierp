<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام ERP متعدد الشركات - تسجيل الدخول">
    <title>تسجيل الدخول - نظام ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
            position: relative;
            overflow: hidden;
        }
        
        .login-page::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 106, 0, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(255, 106, 0, 0.3);
        }
        
        .login-logo i {
            font-size: 36px;
            color: white;
        }
        
        .login-header h1 {
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.3s;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 14px 48px 14px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.2);
        }
        
        .input-wrapper input:focus + i {
            color: var(--primary);
        }
        
        .input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .company-select {
            width: 100%;
            padding: 14px 48px 14px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            appearance: none;
        }
        
        .company-select option {
            background: #1a1a2e;
            color: white;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: var(--primary-light);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 106, 0, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }
        
        .btn-login.loading .spinner {
            display: block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="تبديل الثيم">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>
    
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="fas fa-building"></i>
                    </div>
                    <h1>نظام ERP</h1>
                    <p>نظام إدارة الموارد متعدد الشركات</p>
                </div>
                
                <div id="alertContainer"></div>
                
                <form id="loginForm" onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="username">اسم المستخدم</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" 
                                   placeholder="أدخل اسم المستخدم" required autocomplete="username">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">كلمة المرور</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" 
                                   placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" id="companySelectGroup" style="display: none;">
                        <label for="company">اختر الشركة</label>
                        <div class="input-wrapper">
                            <select id="company" name="company_id" class="company-select">
                                <option value="">-- اختر الشركة --</option>
                            </select>
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    
                    <div class="remember-forgot">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" id="remember">
                            <span>تذكرني</span>
                        </label>
                        <a href="#" class="forgot-link">نسيت كلمة المرور؟</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt"></i>
                            تسجيل الدخول
                        </span>
                        <span class="spinner"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '../api/v1';
        
        function showAlert(message, type = 'error') {
            const container = document.getElementById('alertContainer');
            container.innerHTML = `
                <div class="alert alert-${type}">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
        }
        
        function clearAlert() {
            document.getElementById('alertContainer').innerHTML = '';
        }
        
        async function handleLogin(e) {
            e.preventDefault();
            clearAlert();
            
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const companyId = document.getElementById('company').value;
            
            try {
                const response = await fetch(`${API_URL}/auth/login.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        company_id: companyId || null
                    }),
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || 'خطأ في الاتصال بالخادم: ' + response.status);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // حفظ التوكن
                    localStorage.setItem('token', data.data.token);
                    localStorage.setItem('refresh_token', data.data.refresh_token);
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    localStorage.setItem('company', JSON.stringify(data.data.company));
                    
                    // إذا كان لديه أكثر من شركة، عرض قائمة الاختيار
                    if (data.data.companies && data.data.companies.length > 1 && !companyId) {
                        showCompanySelect(data.data.companies);
                        btn.classList.remove('loading');
                        return;
                    }
                    
                    // حفظ الجلسة في PHP
                    await fetch('session_login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: data.data.user.id,
                            company_id: data.data.company.id,
                            token: data.data.token
                        }),
                        credentials: 'same-origin'
                    });
                    
                    showAlert('تم تسجيل الدخول بنجاح، جاري التحويل...', 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(data.message || 'فشل تسجيل الدخول');
                    btn.classList.remove('loading');
                }
            } catch (error) {
                showAlert(error.message || 'حدث خطأ في الاتصال بالخادم');
                btn.classList.remove('loading');
                console.error('Login error:', error);
            }
        }
        
        function showCompanySelect(companies) {
            const select = document.getElementById('company');
            select.innerHTML = '<option value="">-- اختر الشركة --</option>';
            
            companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.id;
                option.textContent = company.name;
                if (company.is_default) option.selected = true;
                select.appendChild(option);
            });
            
            document.getElementById('companySelectGroup').style.display = 'block';
            showAlert('لديك أكثر من شركة، يرجى اختيار الشركة', 'success');
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            
            if (html.getAttribute('data-theme') === 'light') {
                html.setAttribute('data-theme', 'dark');
                icon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'dark');
            } else {
                html.setAttribute('data-theme', 'light');
                icon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'light');
            }
        }
        
        // تحميل الثيم المحفوظ
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.getElementById('themeIcon').className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        });
    </script>
</body>
</html>
