<?php
// نظام جدولة المنشورات - Post scheduling system
require_once 'config.php';
require_once 'functions.php';
require_once 'content_generator.php';

/**
 * معالجة المنشورات المجدولة - Process scheduled posts
 */
function processScheduledPosts() {
    $scheduledPosts = getScheduledPosts();
    $currentTime = time();
    $processed = false;
    
    foreach ($scheduledPosts as $post) {
        // نشر المنشورات المستحقة فقط - Only post due posts
        if ($post['status'] === 'pending' && $post['scheduled_time'] <= $currentTime) {
            $result = postToChannel($post['content']);
            
            if (isset($result['ok']) && $result['ok']) {
                updatePostStatus($post['id'], 'published');
            } else {
                updatePostStatus($post['id'], 'failed');
                logError('فشل نشر المحتوى المجدول', json_encode($result));
            }
            
            $processed = true;
        }
    }
    
    // تنظيف المنشورات القديمة - Clean up old posts
    cleanupOldPosts();
    
    return $processed;
}

/**
 * تنظيف المنشورات القديمة - Clean up old posts
 */
function cleanupOldPosts() {
    $scheduledPosts = getScheduledPosts();
    $currentTime = time();
    $oneWeekAgo = $currentTime - (7 * 24 * 60 * 60); // أسبوع واحد - One week
    
    $updatedPosts = array_filter($scheduledPosts, function($post) use ($oneWeekAgo) {
        // الاحتفاظ بالمنشورات المعلقة وتلك التي تم نشرها في الأسبوع الماضي
        // Keep pending posts and those published in the last week
        return $post['status'] === 'pending' || 
               ($post['status'] === 'published' && $post['scheduled_time'] >= $oneWeekAgo);
    });
    
    file_put_contents(SCHEDULED_POSTS_FILE, json_encode(array_values($updatedPosts)));
}

/**
 * جدولة منشورات لليوم - Schedule posts for the day
 */
function schedulePostsForToday() {
    $settings = getSettings();
    $scheduledPosts = getScheduledPosts();
    
    // التحقق مما إذا كان النشر نشطًا - Check if posting is active
    if (!$settings['active']) {
        return false;
    }
    
    // احسب عدد المنشورات المجدولة لليوم - Count scheduled posts for today
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    $todayPosts = array_filter($scheduledPosts, function($post) use ($today, $tomorrow) {
        return $post['scheduled_time'] >= $today && $post['scheduled_time'] < $tomorrow;
    });
    
    // عدد المنشورات المراد إنشاؤها - Number of posts to create
    $postsToCreate = $settings['daily_posts'] - count($todayPosts);
    
    if ($postsToCreate <= 0) {
        return false; // لا حاجة لإنشاء منشورات جديدة - No need to create new posts
    }
    
    // إنشاء وجدولة المنشورات الجديدة - Create and schedule new posts
    $dayHours = 24; // ساعات اليوم - Hours in a day
    $postInterval = $dayHours / $settings['daily_posts']; // الفاصل الزمني بين المنشورات بالساعات - Hours between posts
    
    // تحديد أوقات النشر المتبقية - Determine remaining posting times
    $postTimes = [];
    $startTime = $today + (9 * 3600); // البداية من الساعة 9 صباحًا - Start at 9 AM
    
    for ($i = 0; $i < $settings['daily_posts']; $i++) {
        $postTime = $startTime + ($i * $postInterval * 3600);
        
        // إضافة بعض العشوائية (±30 دقيقة) - Add some randomness (±30 minutes)
        $postTime += mt_rand(-1800, 1800);
        
        // التأكد من أن وقت النشر ليس في الماضي - Ensure posting time is not in the past
        if ($postTime > time()) {
            $postTimes[] = $postTime;
        }
    }
    
    // إزالة أوقات النشر التي تم جدولتها بالفعل - Remove times that already have scheduled posts
    foreach ($todayPosts as $post) {
        foreach ($postTimes as $key => $time) {
            // إذا كان هناك منشور مجدول ضمن ساعة من الوقت المقترح - If a post is scheduled within an hour of the suggested time
            if (abs($post['scheduled_time'] - $time) < 3600) {
                unset($postTimes[$key]);
                break;
            }
        }
    }
    
    // إعادة ترتيب المصفوفة - Reindex array
    $postTimes = array_values($postTimes);
    
    // إنشاء المنشورات وجدولتها - Create posts and schedule them
    $created = 0;
    foreach ($postTimes as $time) {
        if ($created >= $postsToCreate) {
            break;
        }
        
        $content = createNewPost();
        if ($content !== false) {
            schedulePost($content, $time);
            $created++;
        }
    }
    
    return $created;
}
?>
