<!DOCTYPE HTML>
<html lang=en>
  <head>
    <title>SAVACS ManageInterface [Table View]</title>
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
      div.tableParent {
        overflow-x: auto;
        overflow-y: visible;
      }
      table > thead > tr > th {
        background-color: lightblue;
      }
      table > tbody > tr:nth-child(even) > td {
        background-color: lightyellow;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <h1>SAVACS ManageInterface</h1>
      <p>[Table View]</p>
      <p><?php echo (string)count($colnames); ?> cols / <?php echo (string)count($rows); ?> rows found.</p>
      <div class=tableParent>
        <table border=1>
          <thead>
            <tr>
              <?php foreach($colnames as $colname) { ?><th><?php echo (string)$colname; ?></th><?php } ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $row) { ?><tr>
              <?php foreach($row as $col) { ?><td align=<?php echo gettype($col) == "integer" || gettype($col) == "double" ? "right" : "left";?>><?php echo (string)$col; ?></td><?php } ?>
            </tr><?php } ?>
          </tbody>
        </table>
      </div>
      <button onclick=location.href="../">&lt; Back</button>
    </div>
  </body>
</html>

