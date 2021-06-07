<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="pagewidth" content="width=device-width">
  <meta name="author" content="Güren Tan Dinga">
  <title>Point Cloud Portal</title>
  <link rel="stylesheet" href="./css/pcsstyle.css">
</head>

<body>
  <header>
    <div class="container">
      <div id="kopf">
        <h1>Point Cloud Portal</h1>
      </div>
      <nav>
        <ul>
          <li><a href="./index.php">Index</a></li>
          <li><a href="./upload.php">Kontrollzentrum</a></li>
          <li><a href="./hilfe.html">Hilfe</a></li>
          <li><a href="mailto:gueren.dinga@hcu-hamburg.de">Kontakt</a></li>
        </ul>
      </nav>
    </div>
  </header>


<!-- ÜBERARBEITETER CODE ZUR DARSTELLUNG -->
  <div class="row" id="inhalt">
    <div class="column" style="background-color: #e8e8e8;">
      <?php
      $pwdb = new PDO('sqlite:./pwdb.db');

      print "<table>";
      $result = $pwdb->query('SELECT * FROM pointclouds');
      foreach($result as $row) {
        print "<tr><td><h3>".$row['Id'].": ".$row['name']. " " . $row['mobile']. " " . $row['lite']. " " . $row['pro']."</h3></td> ";
        print "</table>";
        print "<b>Autor: </b>";
        print $row['autor'];
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "<b>Lizenz: </b>";
        print $row['lizenz'];
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        print "<b>Tags: </b>";
        print $row['tags'];
        print "<br><br><b> Beschreibung: </b>";
        print $row['description']; "<br><br>";
        print "<hr>";};
      print "</table>";
      ?>
      </p>
  </div></div>
</body>
</html>
