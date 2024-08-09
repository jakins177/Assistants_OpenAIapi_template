<?php
$api_key = '';
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['threadId']) || !isset($input['message'])) {
  echo json_encode(['error' => 'Thread ID and message are required.']);
  exit;
}

$thread_id = $input['threadId'];
$message_content = $input['message'];

$url = 'https://api.openai.com/v1/threads/' . $thread_id . '/messages';

$headers = [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $api_key,
  'OpenAI-Beta: assistants=v2'
];

$data = json_encode([
  'role' => 'user',
  'content' => $message_content
]);

$options = [
  'http' => [
    'header'  => implode("\r\n", $headers),
    'method'  => 'POST',
    'content' => $data,
  ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
  echo json_encode(['error' => 'Error sending message']);
} else {
  echo $result;
}
?>
