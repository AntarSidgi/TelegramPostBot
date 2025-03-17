<?php
// معالج ويب هوك تيليجرام - Telegram Webhook Handler
require_once 'config.php';
require_once 'functions.php';
require_once 'content_generator.php';
require_once 'scheduler.php';
require_once 'admin_panel.php';

// تسجيل الخطأ في حالة وجود استثناء - Log error in case of exception
set_exception_handler(function($e) {
    logError('استثناء غير معالج', $e->getMessage() . ' في ' . $e->getFile() . ':' . $e->getLine());
});

/**
 * المعالج الرئيسي - Main handler
 */
function handleWebhook() {
    // الحصول على البيانات من تيليجرام - Get data from Telegram
    $update = json_decode(file_get_contents('php://input'), true);
    
    // تسجيل التحديثات الواردة (للتصحيح فقط) - Log incoming updates (for debugging only)
    // file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($update) . "\n", FILE_APPEND);
    
    try {
        if (isset($update['message'])) {
            processMessage($update['message']);
        }
    } catch (Exception $e) {
        logError('خطأ في معالجة التحديث', $e->getMessage());
    }
    
    // معالجة المنشورات المجدولة - Process scheduled posts
    try {
        processScheduledPosts();
    } catch (Exception $e) {
        logError('خطأ في معالجة المنشورات المجدولة', $e->getMessage());
    }
    
    // ضمان إعداد منشورات لليوم الحالي - Ensure posts are set up for today
    try {
        $settings = getSettings();
        if ($settings['active']) {
            schedulePostsForToday();
        }
    } catch (Exception $e) {
        logError('خطأ في جدولة منشورات اليوم', $e->getMessage());
    }
}

/**
 * معالجة الرسائل الواردة - Process incoming messages
 */
function processMessage($message) {
    // التعامل مع الرسائل النصية فقط - Handle text messages only
    if (!isset($message['text'])) {
        return;
    }
    
    $text = $message['text'];
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    
    // معالجة أوامر المشرف - Process admin commands
    if (isAdmin($user_id)) {
        handleAdminCommand($message);
    } else {
        // رد للمستخدمين غير المصرح لهم - Response for unauthorized users
        sendTelegramMessage($chat_id, "عذراً، هذا البوت مخصص للمشرفين فقط.");
    }
}

// التنفيذ الرئيسي - Main execution
handleWebhook();
?>
