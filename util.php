<?php
require_once(__DIR__ . '/util.inc.php');

function GetPath($id) {
  $path = null;
  if (isset($id) && strlen($id) && strpos($id, '_') == 6) {
    $path = realpath(__DIR__ . '/results');
    if ($path) {
      $path .= '/netlog';
      $parts = explode('_', $id);
      $parts2 = str_split($parts[0], 2);
      foreach($parts2 as $part) {
        $path .= "/$part";
      }
      $path .= "/{$parts[1]}";
    }
  }
  return $path;
}

function getRequests() {
  $requests = null;
  $path = GetPath($_REQUEST['id']);
  if (isset($path)) {
    $requests_file = "$path/requests.json.gz";
    $file = gzopen($requests_file, 'r');
    if ($file) {
      $data = '';
      while (!gzeof($file)) {
        $data .= gzread($file, 4096);
      }      
      $requests = json_decode($data, true);
    }
  }

  // Filter out the spurious new tab requests
  $needs_timing = false;
  $ignore = array(
    'edgedl.me.gvt1.com',
    'update.googleapis.com',
    'www.google.com'
  );
  do {
    $removed = false;
    if (count($requests)) {
      $host = parse_url($requests[0]['url'], PHP_URL_HOST);
      if (in_array($host, $ignore)) {
        array_shift($requests);
        $needs_timing = true;
        $removed = true;
      }
    }
  } while($removed);

  // Adjust the request timings if a request was removed
  if ($needs_timing) {
    $keys = array(
      'created',
      'start',
      'end',
      'first_byte',
      'connect_start',
      'connect_end',
      'dns_start',
      'dns_end',
      'ssl_start',
      'ssl_end'
    );
    $lowest = null;
    // First pass, find the lowest non-zero timing value
    foreach($requests as &$request) {
      foreach($keys as $key) {
        if (isset($request[$key]) && $request[$key] > 0) {
          if (!isset($lowest) || $request[$key] < $lowest) {
            $lowest = $request[$key];
          }
        }
      }
    }
    // Adjust the actual timings
    if (isset($lowest)) {
      foreach($requests as &$request) {
        foreach($keys as $key) {
          if (isset($request[$key]) && $request[$key] >= $lowest) {
            $request[$key] -= $lowest;
          }
        }
        if (isset($request['chunks'])) {
          foreach($request['chunks'] as &$chunk) {
            if (isset($chunk['ts']) && $chunk['ts'] >= $lowest) {
              $chunk['ts'] -= $lowest;
            }
          }
        }
        }
    }
  }

  // go through the requests and format them in the way that the waterfall code expects
  $index = 0;
  foreach($requests as &$request) {
    $request['index'] = $index;
    $index++;
    $request['number'] = $index;
    $request['is_secure'] = strncmp($request['url'], 'https:', 6) === 0;
    $request['all_start'] = $request['start'];
    $request['all_end'] = $request['end'];
    $request['full_url'] = $request['url'];
    $request['host'] = parse_url($request['url'], PHP_URL_HOST);

    if (isset($request['bytes_in']))
      $request['bytesIn'] = $request['bytes_in'];
    if (isset($request['uncompressed_bytes_in']))
      $request['objectSizeUncompressed'] = $request['uncompressed_bytes_in'];
    if (isset($request['stream_id']))
      $request['http2_stream_id'] = $request['stream_id'];
    if (isset($request['parent_stream_id']))
      $request['http2_stream_dependency'] = $request['parent_stream_id'];
    if (isset($request['server_address']))
      $request['ip_addr'] = $request['server_address'];
    if (isset($request['weight']))
      $request['http2_stream_weight'] = $request['weight'];
    if (isset($request['exclusive']))
      $request['http2_stream_exclusive'] = $request['exclusive'];
    if (isset($request['certificate']))
      $request['certificate_bytes'] = strlen($request['certificate']);
    
    if (isset($request['start']))
      $request['load_start'] = $request['ttfb_start'] = $request['start'];
    if (isset($request['first_byte'])) {
      $request['download_start'] = $request['ttfb_end'] = $request['first_byte'];
      $request['ttfb_ms'] = round($request['first_byte'] - $request['start']);
    }
    if (isset($request['end'])) {
      $request['download_end'] = $request['end'];
      if (isset($request['first_byte']))
        $request['download_ms'] = round($request['end'] - $request['first_byte']);
    }
    if (isset($request['start']) && isset($request['end']))
      $request['all_ms'] = round($request['end'] - $request['start']);

    $request['headers'] = array();
    if (isset($request['response_headers'])) {
      $request['headers']['response'] = $request['response_headers'];
      foreach($request['response_headers'] as $header) {
        if (strncasecmp($header, 'content-type:', 13) == 0) {
          $request['contentType'] = trim(substr($header, 13));
        }
        if (strncasecmp($header, ':status:', 8) == 0) {
          $request['responseCode'] = trim(substr($header, 8));
        }
        if (strncasecmp($header, 'HTTP/1', 6) == 0) {
          $space = strpos($header, ' ');
          if ($space > 0)
            $request['responseCode'] = trim(substr($header, $space + 1, 4));
        }
      }
    }

    if (isset($request['request_headers'])) {
      $request['headers']['request'] = $request['request_headers'];
    }
  }
  return $requests;
}