<?php
// إعداد البوت وتسجيل ويب هوك - Bot setup and webhook registration
require_once 'config.php';

/**
 * تسجيل ويب هوك - Register webhook
 */
function registerWebhook($url) {
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook";
    $params = [
        'url' => $url,
        'max_connections' => 40,
        'allowed_updates' => json_encode(['message'])
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "خطأ في تسجيل ويب هوك: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    $result = json_decode($response, true);
    
    if (!$result['ok']) {
        echo "فشل تسجيل ويب هوك: " . $result['description'] . "\n";
        return false;
    }
    
    echo "تم تسجيل ويب هوك بنجاح!\n";
    return true;
}

/**
 * الحصول على معلومات ويب هوك - Get webhook info
 */
function getWebhookInfo() {
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "خطأ في الحصول على معلومات ويب هوك: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

/**
 * إلغاء تسجيل ويب هوك - Delete webhook
 */
function deleteWebhook() {
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "خطأ في إلغاء تسجيل ويب هوك: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    $result = json_decode($response, true);
    
    return isset($result['ok']) && $result['ok'];
}

/**
 * التحقق من تكوين البوت - Check bot configuration
 */
function checkBotConfiguration() {
    echo "التحقق من تكوين البوت...\n";
    
    // التحقق من وجود مفاتيح API - Check API keys existence
    if (empty(BOT_TOKEN)) {
        echo "خطأ: لم يتم تعيين توكن البوت.\n";
        return false;
    }
    
    if (empty(GEMINI_API_KEY)) {
        echo "خطأ: لم يتم تعيين مفتاح API الخاص بـ Gemini.\n";
        return false;
    }
    
    // التحقق من صلاحية توكن البوت - Check bot token validity
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/getMe";
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "خطأ في الاتصال بـ Telegram API: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    $result = json_decode($response, true);
    
    if (!$result['ok']) {
        echo "خطأ: توكن البوت غير صالح.\n";
        return false;
    }
    
    echo "تم التحقق من توكن البوت: " . $result['result']['username'] . "\n";
    
    // التحقق من وجود مجلد البيانات - Check data directory
    if (!file_exists(DATA_DIR)) {
        if (!mkdir(DATA_DIR, 0755, true)) {
            echo "خطأ: فشل إنشاء مجلد البيانات.\n";
            return false;
        }
        echo "تم إنشاء مجلد البيانات.\n";
    }
    
    echo "تم التحقق من تكوين البوت بنجاح.\n";
    return true;
}

/**
 * اختبار الاتصال بـ Gemini API - Test connection to Gemini API
 */
function testGeminiAPI() {
    echo "اختبار الاتصال بـ Gemini API...\n";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => "قل مرحباً باللغة العربية"
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 100,
        ]
    ];

    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "خطأ في الاتصال بـ Gemini API: " . curl_error($ch) . "\n";
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        echo "خطأ: استجابة غير صالحة من Gemini API\n";
        echo "الاستجابة: " . print_r($result, true) . "\n";
        return false;
    }
    
    echo "تم الاتصال بنجاح بـ Gemini API!\n";
    echo "الرد: " . $result['candidates'][0]['content']['parts'][0]['text'] . "\n";
    return true;
}

/**
 * إرسال رسالة تجريبية إلى المشرف - Send test message to admin
 */
function sendTestMessageToAdmin() {
    echo "إرسال رسالة تجريبية إلى المشرف...\n";
    
    $message = "👋 مرحباً! هذه رسالة تجريبية من بوت النشر التلقائي.\n\n"
             . "تم إعداد البوت بنجاح وهو جاهز للاستخدام.";
    
    $result = sendTelegramMessage(ADMIN_ID, $message);
    
    if (isset($result['ok']) && $result['ok']) {
        echo "تم إرسال الرسالة التجريبية بنجاح!\n";
        return true;
    } else {
        echo "فشل إرسال الرسالة التجريبية: " . print_r($result, true) . "\n";
        return false;
    }
}

// الواجهة الرئيسية للإعداد - Main setup interface
if (php_sapi_name() === "cli") {
    // واجهة سطر الأوامر - Command line interface
    echo "===================================\n";
    echo "إعداد بوت النشر التلقائي لتيليجرام\n";
    echo "===================================\n\n";
    
    if (!checkBotConfiguration()) {
        echo "فشل التحقق من تكوين البوت. يرجى تعديل ملف الإعدادات.\n";
        exit(1);
    }

    // اختبار الاتصال بـ Gemini API - Test Gemini API connection
    if (!testGeminiAPI()) {
        echo "فشل الاتصال بـ Gemini API. يرجى التحقق من المفتاح والاتصال.\n";
        exit(1);
    }

    // عرض معلومات ويب هوك الحالية - Display current webhook info
    $webhookInfo = getWebhookInfo();
    
    if ($webhookInfo && $webhookInfo['ok']) {
        $info = $webhookInfo['result'];
        echo "\nمعلومات ويب هوك الحالي:\n";
        echo "URL: " . ($info['url'] ?? "غير معين") . "\n";
        echo "معلق: " . ($info['pending_update_count'] ?? 0) . " تحديثات\n";
        echo "آخر خطأ: " . ($info['last_error_message'] ?? "لا يوجد") . "\n\n";
    }
    
    // سؤال المستخدم عن URL ويب هوك - Ask user for webhook URL
    echo "أدخل URL لتسجيل ويب هوك (اتركه فارغاً للتخطي): ";
    $url = trim(fgets(STDIN));
    
    if (!empty($url)) {
        if (registerWebhook($url)) {
            echo "تم تسجيل ويب هوك بنجاح على: $url\n";
        } else {
            echo "فشل تسجيل ويب هوك.\n";
        }
    } else {
        echo "تم تخطي تسجيل ويب هوك.\n";
    }

    // إرسال رسالة تجريبية - Send test message
    echo "\nهل ترغب في إرسال رسالة تجريبية للمشرف؟ (y/n): ";
    $answer = trim(fgets(STDIN));
    
    if (strtolower($answer) === 'y') {
        sendTestMessageToAdmin();
    }
    
    echo "\nتم الانتهاء من إعداد البوت! يمكنك الآن استخدام البوت من خلال تيليجرام.\n";
    echo "لتشغيل البوت، يمكنك إضافة مهمة كرون لتنفيذ الملف cron.php بشكل دوري.\n";
    
} else {
    // واجهة المتصفح - Browser interface
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>إعداد بوت النشر التلقائي</title>
        <style>
            body {
                font-family: Arial, Tahoma, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
                color: #333;
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
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .section {
                margin: 20px 0;
                padding: 10px;
                background-color: #f9f9f9;
                border-radius: 5px;
            }
            .success {
                background-color: #d4edda;
                color: #155724;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
            }
            .error {
                background-color: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .form-group input[type="text"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .btn {
                display: inline-block;
                padding: 8px 16px;
                background-color: #4267B2;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            .btn:hover {
                background-color: #365899;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>إعداد بوت النشر التلقائي لتيليجرام</h1>

            <div class="section">
                <h2>التحقق من التكوين</h2>
                <?php
                $configOk = checkBotConfiguration();
                if ($configOk) {
                    echo '<div class="success">تم التحقق من تكوين البوت بنجاح.</div>';
                } else {
                    echo '<div class="error">فشل التحقق من تكوين البوت. يرجى تعديل ملف الإعدادات.</div>';
                }
                ?>
            </div>

            <?php if ($configOk): ?>
                <div class="section">
                    <h2>اختبار Gemini API</h2>
                    <?php
                    if (testGeminiAPI()) {
                        echo '<div class="success">تم الاتصال بـ Gemini API بنجاح!</div>';
                    } else {
                        echo '<div class="error">فشل الاتصال بـ Gemini API. يرجى التحقق من المفتاح.</div>';
                    }
                    ?>
                </div>

                <div class="section">
                    <h2>إدارة ويب هوك</h2>
                    <?php
                    $webhookInfo = getWebhookInfo();
                    if ($webhookInfo && $webhookInfo['ok']) {
                        $info = $webhookInfo['result'];
                        echo '<h3>معلومات ويب هوك الحالي:</h3>';
                        echo '<p>URL: ' . ($info['url'] ?? "غير معين") . '</p>';
                        echo '<p>التحديثات المعلقة: ' . ($info['pending_update_count'] ?? 0) . '</p>';
                        echo '<p>آخر خطأ: ' . ($info['last_error_message'] ?? "لا يوجد") . '</p>';
                    }
                    ?>

                    <form action="" method="post">
                        <div class="form-group">
                            <label for="webhook_url">URL ويب هوك جديد:</label>
                            <input type="text" id="webhook_url" name="webhook_url" placeholder="https://example.com/webhook.php">
                        </div>
                        <button type="submit" name="set_webhook" class="btn">تسجيل ويب هوك</button>
                        <button type="submit" name="delete_webhook" class="btn" style="background-color: #dc3545;">إلغاء ويب هوك</button>
                    </form>

                    <?php
                    if (isset($_POST['set_webhook']) && !empty($_POST['webhook_url'])) {
                        $url = $_POST['webhook_url'];
                        if (registerWebhook($url)) {
                            echo '<div class="success">تم تسجيل ويب هوك بنجاح!</div>';
                        } else {
                            echo '<div class="error">فشل تسجيل ويب هوك.</div>';
                        }
                    } elseif (isset($_POST['delete_webhook'])) {
                        if (deleteWebhook()) {
                            echo '<div class="success">تم إلغاء ويب هوك بنجاح!</div>';
                        } else {
                            echo '<div class="error">فشل إلغاء ويب هوك.</div>';
                        }
                    }
                    ?>
                </div>

                <div class="section">
                    <h2>اختبار البوت</h2>
                    <form action="" method="post">
                        <button type="submit" name="test_message" class="btn">إرسال رسالة تجريبية للمشرف</button>
                    </form>

                    <?php
                    if (isset($_POST['test_message'])) {
                        if (sendTestMessageToAdmin()) {
                            echo '<div class="success">تم إرسال الرسالة التجريبية بنجاح!</div>';
                        } else {
                            echo '<div class="error">فشل إرسال الرسالة التجريبية.</div>';
                        }
                    }
                    ?>
                </div>

                <div class="section">
                    <h2>إعداد كرون CRON</h2>
                    <p>لتشغيل البوت بشكل تلقائي، أضف السطر التالي إلى كرون:</p>
                    <div style="background-color: #eee; padding: 10px; border-radius: 5px; font-family: monospace;">
                        */5 * * * * php <?php echo realpath('cron.php'); ?>
                    </div>
                    <p>هذا سيشغل البوت كل 5 دقائق للتحقق من المنشورات المجدولة.</p>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
