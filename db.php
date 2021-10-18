<?php
    $servername = "localhost"; // DATABASE HOST
    $username = "admin"; // DATABASE USERNAME
    $password = "My1-ç^NiCE;pASwoRD!"; // DATABASE PASSWORD
    $dbname = "mydb"; // DATABASE NAME
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");
    if ($conn->connect_error) {
        die("Unable to Connect database: " . $conn->connect_error);
    }

/*  ==========================
    ===== feedback TABLE =====
    ========= FORMAT =========
    ==========================
                                                             |SharMe special|
    ------------|--------------|--------------|--------------|--------------|--------------|----------------|----------------|
       Col name | ID           | TYPE         | LANG         | USERID       | DISCORDTAG   | TEXT           | ATTACHMENTS    |
    ------------|--------------|--------------|--------------|--------------|--------------|----------------|----------------|
          Index | UNIQUE       |              |              |              |              |                |                |
           Type | bigint(255)  | varchar(255) | char(3)      | bigint(255)  | varchar(37)  | varchar(65535) | varchar(65535) |
         Null ? | false        | true         | true         | false        | true         | false          | true           |
        Default |              | NULL         | NULL         |              | NULL         |                |                |
    ------------|--------------|--------------|--------------|--------------|--------------|----------------|----------------|

*/
