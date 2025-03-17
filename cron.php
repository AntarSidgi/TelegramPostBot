<?php
// مهمة كرون لمعالجة المنشورات المجدولة - CRON task for processing scheduled posts
require_once 'config.php';
require_once 'functions.php';
require_once 'content_generator.php';
require_once 'scheduler.php';

// تسجيل الخطأ في حالة وجود استثناء - Log error in case of exception
set_exception_handler(function($e) {
    logError('استثناء غير معالج في كرون', $e->getMessage() . ' في ' . $e->getFile() . ':' . $e->getLine());
    exit(1);
});

/**
 * الوظيفة الرئيسية لعملية كرون - Main function for CRON job
 */
function runCronJob() {
    try {
        // معالجة المنشورات المجدولة - Process scheduled posts
        $processed = processScheduledPosts();
        
        // جدولة منشورات جديدة حسب الحاجة - Schedule new posts as needed
        $settings = getSettings();
        if ($settings['active']) {
            $created = schedulePostsForToday();
            
            if ($created !== false) {
                echo "تم إنشاء $created منشورات جديدة.\n";
            }
        }
        
        if ($processed) {
            echo "تم معالجة المنشورات المجدولة بنجاح.\n";
        } else {
            echo "لم يتم العثور على منشورات جاهزة للنشر.\n";
        }
        
    } catch (Exception $e) {
        logError('فشلت عملية كرون', $e->getMessage());
        echo "حدث خطأ: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// التنفيذ الرئيسي - Main execution
runCronJob();
?>
