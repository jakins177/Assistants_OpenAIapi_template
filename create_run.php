<?php
$api_key = '';
$data = json_decode(file_get_contents('php://input'), true);

$thread_id = $data['threadId'];
$assistant_id = $data['assistantId'];

$url = 'https://api.openai.com/v1/threads/' . $thread_id . '/runs';
$headers = [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $api_key,
  'OpenAI-Beta: assistants=v2'
];

$body = json_encode([
  'assistant_id' => $assistant_id,
  'stream' => true
]);

$options = [
  'http' => [
    'header' => implode("\r\n", $headers),
    'method' => 'POST',
    'content' => $body,
    'timeout' => 60
  ]
];

$context = stream_context_create($options);
$handle = fopen($url, 'r', false, $context);

if (!$handle) {
  $error_message = 'Error creating run: ' . json_encode(error_get_last());
  error_log($error_message, 3, 'error_log.log');
  http_response_code(500);
  echo json_encode(['error' => $error_message]);
  exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

while (!feof($handle)) {
  $buffer = fgets($handle, 4096);
  if ($buffer !== false) {
    error_log('Received buffer: ' . $buffer, 3, 'debug_log.log');
    // Check if the buffer starts with 'data: ' and strip it off
    $buffer = trim($buffer);
    if (strpos($buffer, 'data: ') === 0) {
      $buffer = substr($buffer, strlen('data: '));
    }
    $data = json_decode($buffer, true);
    if (isset($data['delta']['content'])) {
      foreach ($data['delta']['content'] as $content) {
        if (isset($content['text']['value'])) {
          $text_value = $content['text']['value'];
          error_log('Text value: ' . $text_value, 3, 'debug_log.log');
          echo "data: " . $text_value . "\n\n";
          ob_flush();
          flush();
        } else {
          error_log('No text value found in content', 3, 'debug_log.log');
        }
      }
    } else {
      error_log('No content found in delta', 3, 'debug_log.log');
    }
  } else {
    error_log('Buffer is false', 3, 'debug_log.log');
  }
}

fclose($handle);
echo "data: [DONE]\n\n";
ob_flush();
flush();
?>
