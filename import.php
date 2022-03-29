<?php
require_once(__DIR__ . '/util.php');

if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name']) && file_exists($_FILES['file']['tmp_name'])) {
  $id = date('ymd_') . hash_file('sha1', $_FILES['file']['tmp_name']);
  $path = GetPath($id);
  if (!is_file($path)) {
    if (!is_dir($path))
      mkdir($path, 0777, true);
    $trace_file = "$path/raw.json.gz";
    move_uploaded_file($_FILES['file']['tmp_name'], $trace_file);
    if (file_exists($trace_file)) {
      $requests_file = "$path/requests.json.gz";
      $parser = realpath(__DIR__ . '/trace_parser.py');
      if ($parser) {
        $command = "python3 '$parser' -t '$trace_file' -n '$requests_file'";
        $result = exec($command, $output, $result_code);
      }
    }
  }
  $protocol = getUrlProtocol();
  $view = dirname($_SERVER['PHP_SELF']) . '/view.php';
  header("Location: $protocol://{$_SERVER['HTTP_HOST']}$view?id=$id");
}
