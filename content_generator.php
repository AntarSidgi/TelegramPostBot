<?php
// توليد المحتوى باستخدام Gemini API - Content generation using Gemini API
require_once 'config.php';
require_once 'functions.php';

/**
 * طلب محتوى من Gemini API - Request content from Gemini API
 */
function generateContentWithGemini($contentType) {
    $contentTypes = json_decode(CONTENT_TYPES, true);
    $contentTypeName = isset($contentTypes[$contentType]) ? $contentTypes[$contentType] : 'محتوى تقني';
    
    // بناء رسالة الطلب - Build prompt
    $prompt = "أنت خبير محتوى عربي متخصص في التكنولوجيا. اكتب منشورًا عالي الجودة باللغة العربية الفصحى حول $contentTypeName. ";
    
    // توجيهات محددة حسب نوع المحتوى - Specific directions based on content type
    switch ($contentType) {
        case 'tech_news':
            $prompt .= "يجب أن يكون المنشور عن أحدث الأخبار التقنية، ويشمل التاريخ الحالي والتفاصيل المهمة والتأثيرات المحتملة.";
            break;
        case 'tech_tips':
            $prompt .= "قدم نصيحة تقنية مفيدة يمكن للمستخدمين العاديين تطبيقها لتحسين تجربتهم التقنية أو حماية أجهزتهم.";
            break;
        case 'tech_awareness':
            $prompt .= "اكتب محتوى توعوي حول قضية تقنية مهمة يجب أن يكون المستخدمون على دراية بها، مثل الخصوصية أو الأمن السيبراني.";
            break;
        case 'programming':
            $prompt .= "اكتب نصائح برمجية أو معلومات عن أحدث التقنيات والأدوات المفيدة للمطورين.";
            break;
        case 'cyber_security':
            $prompt .= "قدم معلومات عن تهديد أمني حالي أو نصائح للحماية من الهجمات السيبرانية.";
            break;
    }
    
    $prompt .= " المنشور يجب أن يكون بين 500-700 حرف، مقسم إلى فقرات قصيرة، ومفهوم للجمهور العام، ومنسق بشكل جيد.";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 800,
        ]
    ];

    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        logError('خطأ في طلب Gemini API', curl_error($ch));
        return false;
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        logError('استجابة غير صالحة من Gemini API', $response);
        return false;
    }
    
    $generatedContent = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // تنظيف وتنسيق المحتوى - Clean up and format content
    $generatedContent = cleanupContent($generatedContent);
    
    return $generatedContent;
}

/**
 * تحويل النص إلى صيغة ماركداون تلغرام - Convert text to Telegram Markdown format
 */
function convertToTelegramMarkdown($content) {
    // تحويل العناوين الرئيسية (## Heading) إلى نص غامق (*Heading*)
    // Convert main headings (## Heading) to bold text (*Heading*)
    $content = preg_replace('/^##\s+(.+)$/m', '*$1*', $content);
    
    // تحويل النصوص الغامقة من صيغة ** إلى صيغة *
    // Convert bold text from ** format to * format
    $content = preg_replace('/\*\*(.*?)\*\*/', '*$1*', $content);
    
    // تحويل قوائم النقاط إلى شرطات
    // Convert bullet points to dashes
    $content = preg_replace('/^\*\s+(.+)$/m', '- $1', $content);
    
    // تحويل أقسام المحتوى المعروفة إلى صيغة غامقة
    // Convert known content sections to bold format
    $specialSections = ['نصائح ذهبية', 'أدوات وتقنيات حديثة', 'كلمة أخيرة'];
    foreach ($specialSections as $section) {
        $content = str_replace($section . ':', '*' . $section . ':*', $content);
    }
    
    return $content;
}

/**
 * تنظيف وتنسيق المحتوى - Clean up and format content
 */
function cleanupContent($content) {
    // إزالة علامات الاقتباس إذا وجدت - Remove quotation marks if present
    $content = trim($content, '"\'');
    
    // التأكد من وجود فقرات - Ensure paragraphs
    if (strpos($content, "\n") === false) {
        // تقسيم النص الطويل إلى فقرات - Split long text into paragraphs
        $content = wordwrap($content, 100, "\n\n");
    }
    
    // تحويل المحتوى إلى صيغة ماركداون تلغرام
    // Convert content to Telegram Markdown format
    $content = convertToTelegramMarkdown($content);
    
    // إضافة هاشتاغات مناسبة - Add relevant hashtags
    $hashtags = "\n\n#تطوير #تقنية #برمجة";
    
    return $content . $hashtags;
}

/**
 * إنشاء منشور جديد - Create a new post
 */
function createNewPost() {
    $settings = getSettings();
    
    // اختيار نوع المحتوى بناءً على تفضيلات المحتوى - Choose content type based on preferences
    $contentType = selectContentType($settings['content_preferences']);
    
    // توليد المحتوى - Generate content
    $content = generateContentWithGemini($contentType);
    
    if ($content === false) {
        return false;
    }
    
    return $content;
}

/**
 * اختيار نوع المحتوى بناءً على الأوزان - Select content type based on weights
 */
function selectContentType($preferences) {
    $totalWeight = array_sum($preferences);
    $randomValue = mt_rand(1, $totalWeight);
    
    $currentWeight = 0;
    foreach ($preferences as $type => $weight) {
        $currentWeight += $weight;
        if ($randomValue <= $currentWeight) {
            return $type;
        }
    }
    
    // في حالة الفشل، إرجاع نوع افتراضي - Return default type in case of failure
    return 'tech_tips';
}
?>
