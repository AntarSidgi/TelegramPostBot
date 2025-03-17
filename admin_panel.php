<?php
// لوحة التحكم للمشرف - Admin Panel
require_once 'config.php';
require_once 'functions.php';
require_once 'content_generator.php';
require_once 'scheduler.php';

/**
 * معالجة أوامر لوحة التحكم - Process admin panel commands
 */
function handleAdminCommand($message) {
    $text = $message['text'];
    $chat_id = $message['chat']['id'];
    
    // التحقق من صلاحية المشرف - Verify admin
    if (!isAdmin($message['from']['id'])) {
        sendTelegramMessage($chat_id, "⛔ غير مصرح لك باستخدام هذا البوت.");
        return;
    }

    if ($text === '/start' || $text === 'مرحبا' || $text === 'مرحباً' || $text === 'السلام عليكم') {
        showMainMenu($chat_id);
    } 
    elseif ($text === '📊 إحصائيات') {
        showStatistics($chat_id);
    }
    elseif ($text === '📅 المنشورات المجدولة') {
        showScheduledPosts($chat_id);
    }
    elseif ($text === '⚙️ الإعدادات') {
        showSettings($chat_id);
    }
    elseif ($text === '📝 إنشاء منشور') {
        createManualPost($chat_id);
    }
    elseif ($text === '📋 سجل النشر') {
        showPostingLogs($chat_id);
    }
    elseif ($text === '📢 نشر الآن') {
        publishNow($chat_id);
    }
    elseif ($text === '⏰ جدولة النشر') {
        showScheduleOptions($chat_id);
    }
    elseif ($text === '🔄 إنشاء محتوى آخر') {
        createManualPost($chat_id);
    }
    elseif ($text === '🔙 العودة للقائمة الرئيسية') {
        showMainMenu($chat_id);
    }
    elseif (strpos($text, '/schedule') === 0) {
        handleScheduleCommand($chat_id, $text);
    }
    elseif (strpos($text, '/posts') === 0) {
        $count = intval(str_replace('/posts ', '', $text));
        if ($count > 0) {
            updateSettings(['daily_posts' => $count]);
            sendTelegramMessage($chat_id, "✅ تم تحديث عدد المنشورات اليومية إلى $count");
        } else {
            sendTelegramMessage($chat_id, "❌ يرجى إدخال رقم صحيح أكبر من الصفر");
        }
    }
    elseif (strpos($text, '/delete') === 0) {
        handleDeletePost($chat_id, $text);
    }
    elseif ($text === '/active') {
        activatePosting($chat_id);
    }
    elseif ($text === '/inactive') {
        deactivatePosting($chat_id);
    }
    elseif ($text === '/refresh') {
        $created = schedulePostsForToday();
        sendTelegramMessage($chat_id, "✅ تم تحديث جدول المنشورات: تمت إضافة $created منشورات جديدة");
    }
    else {
        sendTelegramMessage($chat_id, "أمر غير معروف. استخدم القائمة الرئيسية.");
    }
}

/**
 * إظهار القائمة الرئيسية - Show main menu
 */
function showMainMenu($chat_id) {
    $keyboard = [
        ['📊 إحصائيات', '📅 المنشورات المجدولة'],
        ['📝 إنشاء منشور', '⚙️ الإعدادات'],
        ['📋 سجل النشر']
    ];
    
    $replyMarkup = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true,
        'one_time_keyboard' => false
    ];
    
    $welcomeMessage = "مرحباً بك في لوحة تحكم بوت النشر التلقائي 👋\n\n";
    $welcomeMessage .= "استخدم الأزرار أدناه للتحكم في البوت وإدارة المنشورات.";
    
    sendMessage($chat_id, $welcomeMessage, 'HTML', json_encode($replyMarkup));
}

/**
 * إظهار الإحصائيات - Show statistics
 */
function showStatistics($chat_id) {
    $settings = getSettings();
    $scheduledPosts = getScheduledPosts();
    $logs = json_decode(file_get_contents(POSTING_LOG_FILE), true);
    
    $pendingPosts = 0;
    $publishedToday = 0;
    $failedToday = 0;
    
    $today = strtotime('today');
    
    foreach ($scheduledPosts as $post) {
        if ($post['status'] === 'pending') {
            $pendingPosts++;
        }
    }
    
    foreach ($logs as $log) {
        if ($log['time'] >= $today) {
            if ($log['status'] === 'success') {
                $publishedToday++;
            } elseif ($log['status'] === 'failed') {
                $failedToday++;
            }
        }
    }
    
    $stats = "📊 <b>إحصائيات البوت</b>\n\n";
    $stats .= "✅ منشورات تم نشرها اليوم: $publishedToday\n";
    $stats .= "⏳ منشورات في قائمة الانتظار: $pendingPosts\n";
    $stats .= "❌ منشورات فشل نشرها اليوم: $failedToday\n\n";
    $stats .= "⚙️ عدد المنشورات اليومية: {$settings['daily_posts']}\n";
    $stats .= "🔄 حالة النشر التلقائي: " . ($settings['active'] ? "مفعل ✅" : "متوقف ❌");
    
    sendTelegramMessage($chat_id, $stats);
}

/**
 * إظهار المنشورات المجدولة - Show scheduled posts
 */
function showScheduledPosts($chat_id) {
    $scheduledPosts = getScheduledPosts();
    
    if (empty($scheduledPosts)) {
        sendTelegramMessage($chat_id, "📅 لا توجد منشورات مجدولة حالياً.");
        return;
    }
    
    // ترتيب المنشورات حسب موعد النشر - Sort posts by scheduled time
    usort($scheduledPosts, function($a, $b) {
        return $a['scheduled_time'] - $b['scheduled_time'];
    });
    
    $pendingPosts = array_filter($scheduledPosts, function($post) {
        return $post['status'] === 'pending';
    });
    
    if (empty($pendingPosts)) {
        sendTelegramMessage($chat_id, "📅 لا توجد منشورات معلقة في قائمة الانتظار.");
        return;
    }
    
    $message = "📅 <b>المنشورات المجدولة القادمة:</b>\n\n";
    
    $count = 0;
    foreach ($pendingPosts as $post) {
        $count++;
        $dateTime = formatArabicDateTime($post['scheduled_time']);
        $contentPreview = mb_substr($post['content'], 0, 50) . '...';
        
        $message .= "<b>$count. $dateTime</b>\n";
        $message .= "<i>$contentPreview</i>\n\n";
        
        // اظهار فقط 5 منشورات لتجنب الرسائل الطويلة - Show only 5 posts to avoid long messages
        if ($count >= 5) {
            $remaining = count($pendingPosts) - 5;
            if ($remaining > 0) {
                $message .= "و $remaining منشورات أخرى...";
            }
            break;
        }
    }
    
    $message .= "\nلحذف منشور، استخدم الأمر: /delete [معرف المنشور]";
    
    sendTelegramMessage($chat_id, $message);
}

/**
 * إظهار سجل النشر - Show posting logs
 */
function showPostingLogs($chat_id) {
    $logs = json_decode(file_get_contents(POSTING_LOG_FILE), true);
    
    if (empty($logs)) {
        sendTelegramMessage($chat_id, "📋 لا يوجد سجل نشر حالياً.");
        return;
    }
    
    // ترتيب السجلات من الأحدث إلى الأقدم - Sort logs from newest to oldest
    $logs = array_reverse($logs);
    $logs = array_slice($logs, 0, 10); // إظهار آخر 10 سجلات فقط - Show only the last 10 logs
    
    $message = "📋 <b>آخر 10 عمليات نشر:</b>\n\n";
    
    foreach ($logs as $index => $log) {
        $status = $log['status'] === 'success' ? "✅" : "❌";
        $dateTime = formatArabicDateTime($log['time']);
        $contentPreview = mb_substr($log['content'], 0, 40) . '...';
        
        $message .= "<b>$status $dateTime</b>\n";
        $message .= "<i>$contentPreview</i>\n\n";
    }
    
    sendTelegramMessage($chat_id, $message);
}

/**
 * إظهار الإعدادات - Show settings
 */
function showSettings($chat_id) {
    $settings = getSettings();
    $contentTypes = json_decode(CONTENT_TYPES, true);
    
    $message = "⚙️ <b>إعدادات البوت</b>\n\n";
    $message .= "📊 عدد المنشورات اليومية: {$settings['daily_posts']}\n";
    $message .= "🔄 حالة النشر التلقائي: " . ($settings['active'] ? "مفعل ✅" : "متوقف ❌") . "\n\n";
    $message .= "<b>تفضيلات المحتوى:</b>\n";
    
    foreach ($settings['content_preferences'] as $type => $weight) {
        $name = isset($contentTypes[$type]) ? $contentTypes[$type] : $type;
        $percentage = round(($weight / array_sum($settings['content_preferences'])) * 100);
        $message .= "- $name: $percentage%\n";
    }
    
    $message .= "\n<b>الأوامر المتاحة:</b>\n";
    $message .= "/posts [العدد] - تعديل عدد المنشورات اليومية\n";
    $message .= "/active - تفعيل النشر التلقائي\n";
    $message .= "/inactive - إيقاف النشر التلقائي\n";
    $message .= "/refresh - تحديث جدول المنشورات لليوم الحالي\n";
    
    sendTelegramMessage($chat_id, $message);
}

/**
 * إنشاء منشور يدوي - Create manual post
 */
function createManualPost($chat_id) {
    $content = createNewPost();
    
    if ($content === false) {
        sendTelegramMessage($chat_id, "❌ حدث خطأ أثناء إنشاء المحتوى. يرجى المحاولة مرة أخرى.");
        return;
    }
    
    $preview = "📝 <b>تم إنشاء منشور جديد:</b>\n\n";
    $preview .= $content . "\n\n";
    $preview .= "اختر أحد الخيارات التالية:";
    
    $keyboard = [
        ['📢 نشر الآن', '⏰ جدولة النشر'],
        ['🔄 إنشاء محتوى آخر', '🔙 العودة للقائمة الرئيسية']
    ];
    
    $replyMarkup = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true,
        'one_time_keyboard' => true
    ];
    
    // حفظ المحتوى مؤقتًا في إعدادات المستخدم - Temporarily save content in user settings
    $userSettings = [
        'temp_content' => $content,
        'last_action' => 'create_post'
    ];
    updateSettings(['user_' . $chat_id => $userSettings]);
    
    sendMessage($chat_id, $preview, 'HTML', json_encode($replyMarkup));
}

/**
 * تفعيل النشر التلقائي - Activate automatic posting
 */
function activatePosting($chat_id) {
    updateSettings(['active' => true]);
    schedulePostsForToday();
    sendTelegramMessage($chat_id, "✅ تم تفعيل النشر التلقائي");
}

/**
 * إيقاف النشر التلقائي - Deactivate automatic posting
 */
function deactivatePosting($chat_id) {
    updateSettings(['active' => false]);
    sendTelegramMessage($chat_id, "❌ تم إيقاف النشر التلقائي");
}

/**
 * معالجة أمر الجدولة - Handle schedule command
 */
function handleScheduleCommand($chat_id, $text) {
    $settings = getSettings();
    $userId = 'user_' . $chat_id;
    
    if (!isset($settings[$userId]) || !isset($settings[$userId]['temp_content'])) {
        sendTelegramMessage($chat_id, "❌ لا يوجد محتوى لجدولته. يرجى إنشاء منشور أولاً.");
        return;
    }
    
    $content = $settings[$userId]['temp_content'];
    $parts = explode(' ', $text);
    
    if (count($parts) !== 3) {
        sendTelegramMessage($chat_id, "❌ صيغة الأمر غير صحيحة. استخدم: /schedule HH MM");
        return;
    }
    
    $hour = intval($parts[1]);
    $minute = intval($parts[2]);
    
    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        sendTelegramMessage($chat_id, "❌ وقت غير صالح. الساعة من 0-23 والدقائق من 0-59");
        return;
    }
    
    $today = strtotime('today');
    $scheduledTime = $today + ($hour * 3600) + ($minute * 60);
    
    // إذا كان الوقت في الماضي، جدوله لليوم التالي - If time is in the past, schedule for next day
    if ($scheduledTime <= time()) {
        $scheduledTime += 86400; // 24 ساعة - 24 hours
    }
    
    $post = schedulePost($content, $scheduledTime);
    $dateTime = formatArabicDateTime($scheduledTime);
    
    sendTelegramMessage($chat_id, "✅ تمت جدولة المنشور بنجاح في $dateTime");
    
    // مسح المحتوى المؤقت - Clear temporary content
    unset($settings[$userId]);
    updateSettings($settings);
    
    // إظهار القائمة الرئيسية - Show main menu
    showMainMenu($chat_id);
}

/**
 * نشر المحتوى المؤقت على الفور - Publish temporary content immediately
 */
function publishNow($chat_id) {
    $settings = getSettings();
    $userId = 'user_' . $chat_id;
    
    if (!isset($settings[$userId]) || !isset($settings[$userId]['temp_content'])) {
        sendTelegramMessage($chat_id, "❌ لا يوجد محتوى للنشر. يرجى إنشاء منشور أولاً.");
        return;
    }
    
    $content = $settings[$userId]['temp_content'];
    $result = postToChannel($content);
    
    if (isset($result['ok']) && $result['ok']) {
        sendTelegramMessage($chat_id, "✅ تم نشر المحتوى بنجاح في القناة.");
        
        // مسح المحتوى المؤقت - Clear temporary content
        unset($settings[$userId]);
        updateSettings($settings);
    } else {
        sendTelegramMessage($chat_id, "❌ فشل نشر المحتوى في القناة. يرجى المحاولة مرة أخرى.");
    }
    
    // إظهار القائمة الرئيسية - Show main menu
    showMainMenu($chat_id);
}

/**
 * إظهار خيارات الجدولة - Show scheduling options
 */
function showScheduleOptions($chat_id) {
    $settings = getSettings();
    $userId = 'user_' . $chat_id;
    
    if (!isset($settings[$userId]) || !isset($settings[$userId]['temp_content'])) {
        sendTelegramMessage($chat_id, "❌ لا يوجد محتوى للجدولة. يرجى إنشاء منشور أولاً.");
        return;
    }
    
    // عرض خيارات الجدولة المتاحة - Show available scheduling options
    $message = "⏰ <b>حدد وقتاً لنشر المحتوى:</b>\n\n";
    $message .= "لجدولة المنشور، استخدم الأمر التالي:\n";
    $message .= "<code>/schedule HH MM</code>\n\n";
    $message .= "حيث HH هي الساعة (00-23) و MM هي الدقائق (00-59).\n\n";
    $message .= "⌚ <b>أوقات مقترحة:</b>\n";
    
    $currentHour = intval(date('H'));
    $currentMinute = intval(date('i'));
    
    // اقتراح 3 أوقات للجدولة - Suggest 3 scheduling times
    for ($i = 1; $i <= 3; $i++) {
        $hour = ($currentHour + $i * 2) % 24;
        $command = "/schedule " . sprintf("%02d", $hour) . " 00";
        $message .= "• <code>$command</code> - " . sprintf("%02d", $hour) . ":00\n";
    }
    
    $keyboard = [
        ['🔙 العودة للقائمة الرئيسية']
    ];
    
    $replyMarkup = [
        'keyboard' => $keyboard,
        'resize_keyboard' => true,
        'one_time_keyboard' => false
    ];
    
    sendMessage($chat_id, $message, 'HTML', json_encode($replyMarkup));
}

/**
 * معالجة حذف منشور مجدول - Handle deleting scheduled post
 */
function handleDeletePost($chat_id, $text) {
    // استخراج معرف المنشور من النص - Extract post ID from text
    $postId = trim(str_replace('/delete', '', $text));
    
    if (empty($postId)) {
        sendTelegramMessage($chat_id, "❌ يرجى تحديد معرف المنشور المراد حذفه.");
        return;
    }
    
    $scheduledPosts = getScheduledPosts();
    $found = false;
    
    foreach ($scheduledPosts as $key => $post) {
        if ($post['id'] === $postId) {
            unset($scheduledPosts[$key]);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        file_put_contents(SCHEDULED_POSTS_FILE, json_encode(array_values($scheduledPosts)));
        sendTelegramMessage($chat_id, "✅ تم حذف المنشور بنجاح.");
    } else {
        sendTelegramMessage($chat_id, "❌ لم يتم العثور على المنشور المحدد.");
    }
    
    // عرض المنشورات المجدولة بعد الحذف - Show scheduled posts after deletion
    showScheduledPosts($chat_id);
}

/**
 * إرسال رسالة مع لوحة مفاتيح مخصصة - Send message with custom keyboard
 */
function sendMessage($chat_id, $text, $parse_mode = 'HTML', $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode
    ];
    
    if ($reply_markup !== null) {
        $params['reply_markup'] = $reply_markup;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
