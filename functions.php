<?php
// دوال مساعدة للبوت - Helper Functions for the Bot
require_once 'config.php';

/**
 * إرسال رسالة إلى تيليجرام - Send message to Telegram
 */
function sendTelegramMessage($chat_id, $text, $parse_mode = 'HTML') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

/**
 * نشر محتوى في القناة - Post content to the channel
 */
function postToChannel($content) {
    // تعديل استخدام وضع تنسيق ماركداون - Use Markdown parse mode
    $response = sendTelegramMessage(CHANNEL_ID, $content, 'Markdown');
    
    // تسجيل المنشور في سجل النشر - Log the post
    logPosting([
        'time' => time(),
        'status' => isset($response['ok']) && $response['ok'] ? 'success' : 'failed',
        'content' => $content,
        'response' => $response
    ]);
    
    return $response;
}

/**
 * تسجيل عملية النشر - Log posting activity
 */
function logPosting($logData) {
    $logs = json_decode(file_get_contents(POSTING_LOG_FILE), true);
    $logs[] = $logData;
    
    // الاحتفاظ فقط بآخر 100 سجل - Keep only the last 100 logs
    if (count($logs) > 100) {
        $logs = array_slice($logs, -100);
    }
    
    file_put_contents(POSTING_LOG_FILE, json_encode($logs));
}

/**
 * الحصول على الإعدادات - Get settings
 */
function getSettings() {
    return json_decode(file_get_contents(SETTINGS_FILE), true);
}

/**
 * تحديث الإعدادات - Update settings
 */
function updateSettings($newSettings) {
    $currentSettings = getSettings();
    $updatedSettings = array_merge($currentSettings, $newSettings);
    $updatedSettings['last_updated'] = time();
    file_put_contents(SETTINGS_FILE, json_encode($updatedSettings));
    return $updatedSettings;
}

/**
 * جدولة منشور جديد - Schedule a new post
 */
function schedulePost($content, $postTime) {
    $scheduledPosts = json_decode(file_get_contents(SCHEDULED_POSTS_FILE), true);
    
    $newPost = [
        'id' => uniqid(),
        'content' => $content,
        'scheduled_time' => $postTime,
        'created_at' => time(),
        'status' => 'pending'
    ];
    
    $scheduledPosts[] = $newPost;
    
    // ترتيب المنشورات حسب موعد النشر - Sort posts by scheduled time
    usort($scheduledPosts, function($a, $b) {
        return $a['scheduled_time'] - $b['scheduled_time'];
    });
    
    file_put_contents(SCHEDULED_POSTS_FILE, json_encode($scheduledPosts));
    return $newPost;
}

/**
 * الحصول على المنشورات المجدولة - Get scheduled posts
 */
function getScheduledPosts() {
    return json_decode(file_get_contents(SCHEDULED_POSTS_FILE), true);
}

/**
 * تحديث حالة منشور مجدول - Update scheduled post status
 */
function updatePostStatus($postId, $status) {
    $scheduledPosts = getScheduledPosts();
    
    foreach ($scheduledPosts as &$post) {
        if ($post['id'] === $postId) {
            $post['status'] = $status;
            if ($status === 'published') {
                $post['published_at'] = time();
            }
            break;
        }
    }
    
    file_put_contents(SCHEDULED_POSTS_FILE, json_encode($scheduledPosts));
}

/**
 * التحقق من صلاحيات المشرف - Check admin permissions
 */
function isAdmin($userId) {
    return $userId == ADMIN_ID;
}

/**
 * تنسيق التاريخ والوقت بالعربية - Format date and time in Arabic
 */
function formatArabicDateTime($timestamp) {
    $months = [
        'يناير', 'فبراير', 'مارس', 'إبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ];
    
    $days = [
        'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
    ];
    
    $dayName = $days[date('w', $timestamp)];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    return "$dayName $day $month $year - $time";
}

/**
 * تسجيل الأخطاء - Log errors
 */
function logError($message, $details = '') {
    $logEntry = [
        'time' => time(),
        'message' => $message,
        'details' => $details
    ];
    
    // إرسال إشعار للمشرف - Send notification to admin
    sendTelegramMessage(ADMIN_ID, 
        "❌ خطأ في البوت:\n" . 
        "الرسالة: $message\n" .
        ($details ? "التفاصيل: $details" : "")
    );
    
    // يمكن إضافة التسجيل في ملف - Could add file logging here
}
?>
