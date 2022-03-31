<?php
//Übergibt der Server mit der POST-Methode, prüfe Array nach "pw"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_FILES['pw'])) {
    $file=$_FILES['pw'];

    //preg_replace um bestimmte Zeichen auszuschließen
    $pwname = preg_replace("/[^A-Za-z0-9 ]/", "", $_POST['pwname']);
    $tags = preg_replace("/[^,A-Za-z0-9 ]/", "", $_POST['tags']);
    $autor = preg_replace("/[^A-Za-z0-9 ]/", "", $_POST['autor']);
    $description = preg_replace("/[^,A-Za-z0-9 ]/", "", $_POST['description']);
    $lizenz = preg_replace("/[^,A-Za-z0-9 ]/", "", $_POST['lizenz']);

    //Vergeben der Variablen für spätere Nutzung
    $file_name=$file['name'];
    $file_tmplocation=$file['tmp_name'];
    $file_size=$file['size'];
    $ext_check=$file['error'];

    $file_ext = explode('.',$file_name);
    $file_ext = strtolower(end($file_ext));

    //Dateiendungen pruefen bzw. nur *.las und *.laz zulassen:
    $ext_allow=array('las', 'laz');

    //Trennen der Einträge in $ext_allow
    $ext_string=implode(',',$ext_allow);

    //Befinden sich die Dateiendungen im Array erlaubter Dateiendungen?
    if(in_array($file_ext, $ext_allow)) {
        if($ext_check === 0) {

            //Vergabe des eindeutigen Namens und Hinzufügen der Dateiendung
            $unique_name = uniqid('',true) . '.' . $file_ext;

            //Festlegen des Upload-Verzeichnisses
            $file_destination = './uploads/las/' . $unique_name;

            /* Erzeugen der Verzeichnisse "conv/html & conv/pointclouds", wenn
            diese nicht bereits existieren (mit if "not") */
            if(!is_dir('./conv/pointclouds')) {
              mkdir('./conv/pointclouds');
            }

            if(!is_dir('./conv/html')) {
              mkdir('./conv/html');
            }

            /* Wenn das Verschieben vom temporären Pfad zum Upload-Verzeichniss
            durchgeführt wurde,starte mit shell_exec die Konvertierung der
            Punktwolken. Kopiere (cp) das Punktwolkenverzeichnis in das
            zukünftige Verzeichnis für Punktwolken             */
            if(move_uploaded_file($file_tmplocation, $file_destination)) {
            shell_exec("export LD_LIBRARY_PATH=./uploads;
            ./uploads/PotreeConverter ./uploads/las/$unique_name -o ./conv/$unique_name -p $unique_name;
            cp -R ./conv/$unique_name/pointclouds/* ./conv/pointclouds/$unique_name/;"          );

            $protemplate = './uploads/pro/resources/page_template/viewer_template.html';
            $prodestination = './conv/html/$unique_name.pro.html';

            $mobiletemplate = './uploads/mobile/resources/page_template/viewer_template.html';
            $mobiledestination = "./conv/html/$unique_name.mobile.html";

            $litetemplate = './uploads/lite/resources/page_template/viewer_template.html';
            $litedestination = "./conv/html/$unique_name.lite.html";

            copy($protemplate,$prodestination);
            copy($mobiletemplate,$mobiledestination);
            copy($litetemplate,$litedestination);

            /******************************************************************/

            $includePointcloud = "<!-- INCLUDE POINTCLOUD -->";
            $replace = "Potree.loadPointCloud('../pointclouds/$unique_name/cloud.js', '$unique_name', e => {
            			let pointcloud = e.pointcloud;
            			let material = pointcloud.material;
            			viewer.scene.addPointCloud(pointcloud);
            			material.pointColorType = Potree.PointColorType.RGB;
            			material.size = 1;
            			material.pointSizeType = Potree.PointSizeType.FIXED;
            			material.shape = Potree.PointShape.CIRCLE;
            			viewer.fitToScreen();
            		});";

            /******************************************************************/

            //Datei als Typ String einlesen
            $str=file_get_contents('./conv/html/'.$unique_name.'.mobile.html');

            //Ersetze "$libs" durch "$libsToMobile" in "$str"
            $str=str_replace("$includePointcloud", "$replace", $str);

            //Modifizierte "$str" als $unique_name ablegen
            file_put_contents('./conv/html/'.$unique_name.'.mobile.html', $str);

            //s.o. für "Pro"
            $str=file_get_contents('./conv/html/'.$unique_name.'.pro.html');
            $str=str_replace("$includePointcloud", "$replace", $str);
            file_put_contents('./conv/html/'.$unique_name.'.pro.html', $str);

            //s.o. für "Lite"
            $str=file_get_contents('./conv/html/'.$unique_name.'.lite.html');
            $str=str_replace("$includePointcloud", "$replace", $str);
            file_put_contents('./conv/html/'.$unique_name.'.lite.html', $str);

/* folgende Funktion zum Entfernen des Ordners und aller Unterordner (Angepasst
aus Quelle: https://paulund.co.uk/php-delete-directory-and-files-in-directory)*/

            function delete_files($target) {
                if(is_dir($target)){
                    $files = glob($target . '*', GLOB_MARK);

                    foreach($files as $file){
                        delete_files($file);
                    }
                      if(is_dir($target)){
                        rmdir($target);
                      }
                } elseif(is_file($target)) {
                    unlink($target);
                }
            }
            delete_files("./conv/$unique_name");

            /******************************************************************/

            $pwmobile = '<a href="./conv/html/'.$unique_name.'.mobile.html">Mobile</a>';
            $pwlite = '<a href="./conv/html/'.$unique_name.'.lite.html">Lite</a>';
            $pwhref = '<a href="./conv/html/'.$unique_name.'.pro.html">Pro</a>';

            echo "Die Punktwolke wurde erfolgreich hochgeladen und konvertiert:
            $pwname: $pwmobile $pwlite $pwhref";
            try
            {
              //Datenbank-Datei erstellen, wenn bereits existiert, verbinden
              $pwdb = new PDO('sqlite:pwdb.db');

              //Tabelle erstellen
              $pwdb->exec("CREATE TABLE IF NOT EXISTS pointclouds (
                          Id INTEGER PRIMARY KEY,
                          name TEXT(25),
                          mobile TEXT(120),
                          lite TEXT(120),
                          pro TEXT(120),
                          tags TEXT(50),
                          autor ,
                          description ,
                          lizenz )");

              //Daten bei jedem Upload hinzufügen
              $insert = $pwdb->prepare("INSERT INTO pointclouds (name, mobile, lite, pro, tags, autor, description, lizenz)
                                          VALUES (:name, :mobile, :lite, :pro, :tags, :autor, :description, :lizenz)");
              $insert->execute(array(':name'=>$pwname, ':mobile'=>$pwmobile, ':lite'=>$pwlite, ':pro'=>$pwhref, ':tags'=>$tags, ':autor'=>$autor, ':description'=>$description, ':lizenz'=>$lizenz));

              //Verbindung zur DB-Datei schließen
              $pwdb = NULL;
            }
            catch(PDOException $e)
            {
              print 'Exception : '.$e->getMessage();
            }}}}
    else{
      echo "Die hochgeladene Datei entspricht nicht den zugelassenen Dateiendungen. Zu den zugelassenen Datenendungen gehören $ext_string.";
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="pagewidth" content="width=device-width">
    <meta name="author" content="Güren Tan Dinga">
    <title>Point Cloud Portal</title>
    <link rel="stylesheet" href="./css/upload.css">
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

    <section id="koerper">
        <div class="container">
            <h1>Punktwolke hochladen</h1>
            <p>Die mit <font color="red">*</font> gekennzeichneten Felder sind Pflichtfelder</p>
            <form method="POST" enctype="multipart/form-data" autocomplete="off">
              <input type="text" required maxlength="25" name="pwname"
              placeholder="Punktwolken-Bezeichnung"><font color="red">*</font>
              <input type="text" required maxlength="50" name="tags"
              placeholder="Tags, durch Komma getrennt"><font color="red">*</font>
              <input type="text" required maxlength="30" name="autor"
              placeholder="Autor"><font color="red">*</font> <br> <br>
              <p>Die Maximallänge der Beschreibung beträgt 250 Zeichen.</p>
              <textarea input type="text" required wrap="hard" maxlength="250"
              name = "description" placeholder="Beschreibung" style="width: 555px;
              height:60px"></textarea> <font color="red">*</font> <br><br>
              <input type="text" maxlength="30" name="lizenz" placeholder="Lizenz"><br><br>
              <input type="file" name="pw"> <input type="submit"
              value="Hochladen & konvertieren">
            </form>
              <p><br> <br><h1>Punktwolke entfernen</h1></p>
            <form method="POST" autocomplete="off">
              <input type="text" maxlength="3" name="Id" placeholder="Punktwolken-ID">
              <input type="submit" name="delete" value="Punktwolke entfernen">

              <?php
              if (isset($_POST['delete'])) {
                $pwdb = new PDO('sqlite:./pwdb.db');
                $Id = preg_replace("/[^,0-9]/", "", $_POST['Id']);
                $pwdb->query("DELETE FROM pointclouds WHERE Id='$Id'");
                $pwdb = NULL;
              }
                  ?>

        </div>
    </section>
    </body>
</html>
