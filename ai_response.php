<?php
session_start();
include 'config.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");


$api_key = isset($env['GEMINI_API_KEY']) ? trim($env['GEMINI_API_KEY']) : '';
$input = json_decode(file_get_contents("php://input"), true);
$user_message = $input['message'] ?? $_POST['message'] ?? '';

if (empty($user_message) || empty($api_key)) {
    echo json_encode(['reply' => 'Error: Missing user message or API key.']);
    exit;
}


$productText = "";
if (isset($conn)) {
    $productResult = mysqli_query($conn, "SELECT description, price FROM product");
    if ($productResult) {
        while ($row = mysqli_fetch_assoc($productResult)) {
            $productText .= "- " . $row['description'] . " costs £" . $row['price'] . "\n";
        }
    }
}


$system_prompt = "You are a helpful and polite assistant for a phone shop in Barrhead, Scotland. "
    . "Keep answers short (1-2 sentences). "
    . "Use ONLY these products and prices:\n"
    . ($productText ?: "No products in stock right now.") . "\n\n"
    . "User says: " . $user_message;


$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$api_key";

$data = [
    "contents" => [
        ["parts" => [["text" => $system_prompt]]]
    ]
];


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


if ($http_code !== 200) {
    echo json_encode(['reply' => "I am currently overloaded with requests. Please try again in 1 minute!"]);
    exit;
}

$response_data = json_decode($response, true);
$ai_text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't understand that.";

echo json_encode(['reply' => trim($ai_text)]);
?>