<!DOCTYPE HTML>
<html lang=en>
  <head>
    <title>SAVACS ManageInterface [Text View]</title>
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
      pre {
        background: lightcyan;
        padding: 10px;
        white-space: pre-wrap;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <h1>SAVACS ManageInterface</h1>
      <p>[Text View]</p>
      <pre><?php echo $text; ?></pre>
      <button onclick=location.href="../">&lt; Back</button>
    </div>
  </body>
</html>

