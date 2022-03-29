<?php
// Copyright 2020 Catchpoint Systems Inc.
// Use of this source code is governed by the Polyform Shield 1.0.0 license that can be
// found in the LICENSE.md file.
chdir('..');
require_once(__DIR__ . '/util.php');
require_once(__DIR__ . '/waterfall.inc.php');
$id = $_REQUEST['id'];
$requests = getRequests();
$url = $requests[0]['url'];
?>
<!DOCTYPE html>
<html lang="en-us">
    <head>
        <title>Netlog Waterfall</title>
        <link rel="stylesheet" href="/pagestyle2.css" type="text/css">
        <script src="/waterfall.js"></script>
        <script src="/js/site.js"></script>
        <style type="text/css">
          #test_results-container {
            min-width: 1100px;
          }
        </style>
    </head>
    <body id="custom-waterfall">
        <div id="test_results-container" class="box">
          <div class="test_results">
            <div class="test_results-content">
              <div style="text-align:center;">
                <h1>Netlog Waterfall</h1>
                <?php
                    InsertWaterfall($url, $requests, $id, 1, 0, array(), '', 1);
                ?>
              </div>
            </div>
          </div>
        </div>
    </body>
</html>
