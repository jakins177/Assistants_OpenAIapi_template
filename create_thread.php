<?php
$api_key = '';
$url = 'https://api.openai.com/v1/threads';

$headers = [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $api_key,
  'OpenAI-Beta: assistants=v2'
];

$data = ''; // No data is required in the body for creating a thread

$options = [
  'http' => [
    'header'  => implode("\r\n", $headers),
    'method'  => 'POST',
    'content' => $data,
  ],
];

$context  = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
  $error_message = 'Error creating thread: ' . json_encode(error_get_last());
  error_log($error_message, 3, 'error_log.log');
  echo json_encode(['error' => $error_message]);
} else {
  $response_data = json_decode($result, true);
  if (isset($response_data['error'])) {
    $error_message = 'API Error: ' . json_encode($response_data['error']);
    error_log($error_message, 3, 'error_log.log');
    echo json_encode(['error' => $error_message]);
  } else {
    error_log('Thread created successfully: ' . $result, 3, 'error_log.log');
    echo $result;
  }
}
?>

