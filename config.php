<?php
// تكوين البوت - Bot Configuration

// معلومات API - API Information
define('BOT_TOKEN', '356506150:AAGHAQRldU3v_lVGTG0uL4wDr-QbBWnSnjM');
define('CHANNEL_ID', '@tatwer10');
define('GEMINI_API_KEY', 'AIzaSyAGJ7FmEw_zIeKuLc0NemHkjK47g1PUDXk');
define('ADMIN_ID', '323823995');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

// إعدادات ملفات التخزين - Storage Settings
define('DATA_DIR', __DIR__ . '/data/');
define('SCHEDULED_POSTS_FILE', DATA_DIR . 'scheduled_posts.json');
define('POSTING_LOG_FILE', DATA_DIR . 'posting_log.json');
define('SETTINGS_FILE', DATA_DIR . 'settings.json');

// إعدادات النشر - Posting Settings
define('DEFAULT_DAILY_POSTS', 4); // العدد الافتراضي للمنشورات اليومية
define('MIN_POST_INTERVAL', 2 * 60 * 60); // الحد الأدنى للفاصل الزمني بين المنشورات (بالثواني) - 2 ساعات

// أنواع المحتوى - Content Types
define('CONTENT_TYPES', json_encode([
    'tech_news' => 'أخبار تكنولوجية',
    'tech_tips' => 'نصائح تقنية',
    'tech_awareness' => 'توعية تقنية',
    'programming' => 'برمجة وتطوير',
    'cyber_security' => 'أمن سيبراني'
]));

// إنشاء مجلد البيانات إذا لم يكن موجودًا - Create data directory if it doesn't exist
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// إنشاء ملفات JSON إذا لم تكن موجودة - Create JSON files if they don't exist
if (!file_exists(SCHEDULED_POSTS_FILE)) {
    file_put_contents(SCHEDULED_POSTS_FILE, json_encode([]));
}

if (!file_exists(POSTING_LOG_FILE)) {
    file_put_contents(POSTING_LOG_FILE, json_encode([]));
}

if (!file_exists(SETTINGS_FILE)) {
    $defaultSettings = [
        'daily_posts' => DEFAULT_DAILY_POSTS,
        'active' => true,
        'last_updated' => time(),
        'content_preferences' => [
            'tech_news' => 30,
            'tech_tips' => 30,
            'tech_awareness' => 20,
            'programming' => 10,
            'cyber_security' => 10
        ]
    ];
    file_put_contents(SETTINGS_FILE, json_encode($defaultSettings));
}
?>
