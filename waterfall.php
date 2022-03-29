<?php
// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.
header ("Content-type: image/png");
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/waterfall.inc.php';

// Get all of the requests;
if(isset($_REQUEST['test']) && !isset($_REQUEST['id'])) {
  $_REQUEST['id'] = $_REQUEST['test'];
}
$requests = getRequests();

$rows = GetRequestRows($requests, false, true);

$options = array(
  'use_cpu' => false,
  'use_bw' => false,
  'show_labels' => true,
  'show_chunks' => false,
  'is_mime' => true,
  'is_state' => false,
  'include_js' => false,
  'include_wait' => true,
  'show_user_timing' => false
);

$url = $requests[0]['url'];

$im = GetWaterfallImage($rows, $url, array(), $options, array());

// Spit the image out to the browser.
//header('Last-Modified: ' . gmdate('r'));
//header('Expires: '.gmdate('r', time() + 31536000));
//header('Cache-Control: public, max-age=31536000', true);
imagepng($im);
imagedestroy($im);