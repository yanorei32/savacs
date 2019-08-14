<!DOCTYPE HTML>
<html lang=en>
  <head>
    <title>SAVACS ManageInterface [List View]</title>
    <meta name=viewport content=width=device-width,initial-scale=1>
    <style>
      html, body {
        margin: 0;
        width: 100%;
      }
      .container {
        font-family: monospace;
        max-width: 800px;
        margin: auto;
        width: calc(100%-20px);
        padding-left: 10px;
        padding-right: 10px;
      }
      ol {
        padding-top: 10px;
        padding-right: 10px;
        padding-bottom: 10px;
        margin: 0;
        background: lightcyan;
      }
      ol > li:nth-child(even) {
        background: lightyellow;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <h1>SAVACS ManageInterface</h1>
      <p>[List View]</p>
      <p><?php echo (string)count($array); ?> elements found.</p>
      <ol>
        <?php foreach($array as $e) { ?><li><?php echo (string)$e; ?></li><?php } ?>
      </ol>
      <button onclick=location.href="../">&lt; Back</button>
    </div>
  </body>
</html>

