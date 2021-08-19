<?php

    require("../db.php");
    
    $q = "SELECT * FROM `feedback` WHERE 1 ORDER BY `id` ASC";
    $qres = $conn->query($q);
    
    if (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        
        if (is_dir('./uploads/feedbacks/'.$id)) {
            rmdir_recursive('./uploads/feedbacks/'.$id);
        }
        
        $d = "DELETE FROM `feedback` WHERE id=".$id;
        $dr = $conn->query($d);
        
        header("Content-Type:application/json");
        if (!is_dir('./uploads/feedbacks/'.$id)) echo '{ "code": 200 }';
        else echo '{ "code": 520 }';
        return;
    }
    
    function rmdir_recursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Beta Feedback Admin - SharMe</title>
        <link rel="icon" href="https://beta.sharme.eu/assets/images/sharme-o.png" type="image/png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
        
        <style>
            body {
                overflow-x: visible;
                width: auto;
            }
            table {
                min-width: 1300px;
            }
            table,
            td,
            th {
                border: 1px solid #333;
            }
            table th, table td {
                width: calc((100vw - 2*1rem) / 10);
                vertical-align: middle;
                text-align: center;
            }
            table th.actions, table td.actions {
                width: calc((100vw - 2*1rem) / 20);
            }
            table th.feedback {
                width: calc(((100vw - 2*1rem) / 10)*3);
            }
            table td.files {
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
                width: calc((100vw - 2*1rem) / 10);
            }
        </style>
    </head>
    <body class="h-100 m-0 p-3">
        <table class="w-100 h-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Langue</th>
                    <th>SharMe User</th>
                    <th>Discord Username</th>
                    <th class="feedback">Feedback</th>
                    <th>Attachments</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                    if ($qres->num_rows > 0) {
                        while($row = $qres->fetch_assoc()) {
                            $u = "SELECT * FROM `users` WHERE id=".$row['userid'];
                            $ur = $conn->query($u);
                            
                            if ($ur->num_rows > 0) {
                                while($urow = $ur->fetch_assoc()) {
                                    $user = ' (<a href="https://beta.npy5ypkj3j.sharme.eu/profile/'.$urow['username'].'" target="_blank">@'.$urow['username']."</a>)";
                                }
                            } else {
                                $user = "";
                            }
                            
                            $files = array();
                            
                            if ($row['attachments'] != null) {
                                $atts = glob('/home/l347isz0/uploads/feedbacks/'.$row['id'].'/*', GLOB_BRACE);
                                foreach($atts as $att) {
                                    $mimeType = mime_content_type($att);
                                    $fileType = explode('/', $mimeType)[0];
                                    
                                    $files[basename($att)] = array(
                                        "url" => "https://uploads.sharme.eu/feedbacks/".$row['id']."/".basename($att),
                                        "type" => $fileType
                                    );
                                }
                            }
                            
                            echo '<tr>
                                <td>'.$row['id'].'</td>
                                <td>'.($row['type'] == "bugreport" ? "Rapport de bug" : "Suggestion").'</td>
                                <td>'.($row['lang'] == "en" ? "Anglais" : "Français").'</td>
                                <td>'.$row['userid'].$user.'</td>
                                <td>'.$row['discordtag'].'</td>
                                <td>'.$row['text'].'</td>
                                <td class="files">';
                            
                            foreach($files as $name => $file) {
                                if ($file["type"] == "video") echo '<video style="max-width: 100%;" controls><source src="'.str_replace('"', '\\"', $file["url"]).'"></video><br>';
                                else if ($file["type"] == "image") echo '<img style="max-width: 100%;" src="'.str_replace('"', '\\"', $file["url"]).'"><br>';
                                else echo '<a href="'.str_replace('"', '\\"', $file["url"]).'" target="_blank">'.$name.'</a><br>';
                            }
                            
                            echo'</td>
                                <td class="actions">
                                    <i class="bi bi-trash-fill text-danger" style="cursor: pointer" onclick="del('.$row['id'].')"></i>
                                </td>
                            </tr>';
                        }
                    } else {
                        // If no feedback found
                        echo '<tr>
                            <td colspan="8">No feedback found.</td>
                        </tr>';
                    }
                
                ?>
            </tbody>
        </table>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            // DELETE ONE FEEDBACK
            function del(id) {
                if (confirm("Are you sure to delete this feedback ?")) {
                    $.ajax({
                        url: window.location.pathname,
                        type: "POST",
                        data: {
                            "delete": id
                        },
                        success: function (result) {
                            console.log(result);
                            if (result["code"] == 200) window.location.reload(true);
                            else alert("An error occured.");
                        }
                    });
                }
            }
        </script>
    </body>
</html>