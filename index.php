<?php
/**
 * بوت النشر التلقائي لتيليجرام - Telegram Automatic Posting Bot
 * الصفحة الرئيسية - Main Page
 */

// التحقق من أن الملف تم استدعاؤه مباشرة - Check if file was accessed directly
if (php_sapi_name() === 'cli') {
    // تشغيل من سطر الأوامر - Running from command line
    require_once 'cron.php';
    echo "تم تنفيذ مهمة كرون.\n";
    exit;
}

// التحقق مما إذا كان الطلب من تيليجرام - Check if request is from Telegram
$input = file_get_contents('php://input');
if (!empty($input)) {
    // استدعاء معالج ويب هوك - Call webhook handler
    require_once 'webhook.php';
    exit;
}

// التحقق من وجود معلمة setup - Check for setup parameter
if (isset($_GET['setup'])) {
    // استدعاء صفحة الإعداد - Call setup page
    require_once 'setup.php';
    exit;
}

// عرض صفحة المعلومات - Show info page
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوت النشر التلقائي للمحتوى العربي</title>
    <style>
        body {
            font-family: Arial, Tahoma, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4267B2;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4267B2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #365899;
        }
        .features {
            text-align: right;
            margin: 20px 0;
            padding: 0 20px;
        }
        .feature-item {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>بوت النشر التلقائي للمحتوى العربي</h1>
        <p>مرحبًا بك في نظام إدارة المحتوى التلقائي للقنوات العربية على تيليجرام.</p>
        
        <div class="features">
            <h3>المميزات:</h3>
            <div class="feature-item">✓ إنشاء محتوى عربي تلقائي باستخدام Gemini AI</div>
            <div class="feature-item">✓ جدولة المنشورات على فترات منتظمة</div>
            <div class="feature-item">✓ لوحة تحكم سهلة الاستخدام باللغة العربية</div>
            <div class="feature-item">✓ إحصائيات وتقارير لمراقبة أداء البوت</div>
            <div class="feature-item">✓ نظام تنويع المحتوى لضمان جودة المنشورات</div>
        </div>
        
        <p>لإعداد البوت أو تعديل الإعدادات، انقر على الزر أدناه:</p>
        <a href="?setup" class="btn">إعداد البوت</a>
    </div>
</body>
</html>
