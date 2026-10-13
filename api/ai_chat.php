<?php
// =======================================================
// SkillSwap - AI Chatbot Backend (Gemini API + Smart Fallback)
// =======================================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'reply' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$userMessage = trim($data['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['status' => 'error', 'reply' => 'Please enter a message.']);
    exit;
}

// 1. Check for Gemini API Key (from env or constant)
$geminiApiKey = getenv('GEMINI_API_KEY') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');

if (!empty($geminiApiKey)) {
    $apiReply = call_gemini_api($geminiApiKey, $userMessage);
    if ($apiReply) {
        echo json_encode(['status' => 'success', 'reply' => $apiReply, 'engine' => 'gemini_api']);
        exit;
    }
}

// 2. High-Quality Fallback AI Engine (Campus Skill Advisor)
$smartReply = generate_campus_advisor_response($userMessage);
echo json_encode(['status' => 'success', 'reply' => $smartReply, 'engine' => 'campus_advisor']);
exit;

/**
 * Call Gemini 1.5 Flash API via REST
 */
function call_gemini_api($apiKey, $message) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

    $systemInstruction = "You are SwapBot, the intelligent college AI advisor for SkillSwap. You help college students exchange academic and practical skills (e.g. Python, UI/UX, React, Calculus, Public Speaking). Give actionable, friendly, concise advice in 2-3 short paragraphs or bullet points.";

    $payload = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $systemInstruction . "\n\nUser Question: " . $message]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 350
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resJson = json_decode($response, true);
        return $resJson['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
    return null;
}

/**
 * Built-in Campus Advisor Knowledge Engine
 */
function generate_campus_advisor_response($query) {
    $q = strtolower($query);

    // Greetings
    if (preg_match('/\b(hi|hello|hey|greetings|hola)\b/', $q)) {
        return "👋 Hello there! I'm **SwapBot**, your campus skill exchange advisor. How can I help you today? You can ask me how skill matching works, which skills are trending on campus, or for tips on proposing your first swap!";
    }

    // How SkillSwap works / matching
    if (strpos($q, 'how') !== false && (strpos($q, 'swap') !== false || strpos($q, 'work') !== false || strpos($q, 'match') !== false)) {
        return "✨ **How SkillSwap Works in 3 Easy Steps:**\n\n" .
               "1. **List Your Skills**: Go to *My Skills* and add skills you can teach (e.g., Python) and skills you want to learn (e.g., UI Design).\n" .
               "2. **Smart Matching**: Check the *Skill Matching* page! Our algorithm identifies 100% mutual matches where another student teaches what you want, and wants what you teach!\n" .
               "3. **Send Swap Request**: Click 'Request Swap', add a quick intro, and once accepted, connect and learn together!";
    }

    // Python / Web Dev
    if (strpos($q, 'python') !== false || strpos($q, 'django') !== false || strpos($q, 'backend') !== false) {
        return "🐍 **Python & Backend Skills are High in Demand!**\n\n" .
               "• **Popular Exchanges**: Students teaching Python often swap for UI/UX Design (Figma) or Frontend (React.js).\n" .
               "• **Recommendation**: If you're learning Python, start with basic data structures, then build a simple CLI or Flask app. Check the *Search* tab for peers like Aarav who offer Python mentoring!";
    }

    // Design / UI / UX / Figma
    if (strpos($q, 'design') !== false || strpos($q, 'figma') !== false || strpos($q, 'ui') !== false || strpos($q, 'ux') !== false) {
        return "🎨 **UI/UX & Design Mentorship:**\n\n" .
               "• Designers are actively looking for developers to build functional prototypes for their Figma designs.\n" .
               "• Check out Priya Patel's profile in the Search tab — she offers Figma Prototyping and is looking for Python guidance!";
    }

    // Machine Learning / AI
    if (strpos($q, 'ai') !== false || strpos($q, 'machine learning') !== false || strpos($q, 'data science') !== false) {
        return "🤖 **Machine Learning & AI Path:**\n\n" .
               "• ML requires a solid foundation in Python, NumPy, Pandas, and Linear Algebra.\n" .
               "• On our platform, Rohan Mehta is offering Machine Learning & Scikit-Learn sessions in exchange for React frontend skills. You can initiate a swap with him!";
    }

    // Tips for swapping / etiquette
    if (strpos($q, 'tip') !== false || strpos($q, 'proposal') !== false || strpos($q, 'best') !== false || strpos($q, 'advice') !== false) {
        return "💡 **Tips for a Successful Skill Swap:**\n\n" .
               "• **Be Specific**: In your request message, specify what topic you want to cover and how many hours/week you can dedicate.\n" .
               "• **Equal Give-and-Take**: Plan a 1-hour swap as 30 mins learning and 30 mins teaching.\n" .
               "• **Use Campus Spaces**: College library group study rooms or Google Meet work best for peer sessions!";
    }

    // Admin / Platform stats
    if (strpos($q, 'admin') !== false || strpos($q, 'stat') !== false || strpos($q, 'chart') !== false) {
        return "📊 **Analytics & Moderation:**\n\n" .
               "Our Admin Panel tracks live platform metrics using Chart.js, including top skills in demand, monthly exchange activity, and swap completion rates. Admins can log in using `admin@skillswap.edu`!";
    }

    // Default Fallback
    return "💡 That's a great question! On SkillSwap, you can teach any academic, technical, or creative skill to fellow students.\n\n" .
           "• Browse available peers in the **Search** tab.\n" .
           "• Check **Skill Matching** to see students with reciprocal learning goals.\n" .
           "• Or ask me: *'How to swap?'*, *'Tips for proposal'*, or *'Find Python peers'*!";
}
