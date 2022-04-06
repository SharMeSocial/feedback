<?php
    
    require("db.php");
    session_start();
    
    $webhookurl = "https://ptb.discord.com/api/webhooks/000000000000000000/WEB-HOOK-TOKEN"; // Discord Webhook URL
    $url = "https://discord.com/api/v9/channels/000000000000000000/messages"; // Discord Webhook Channel Messages URL
    $token = "YOUR-BOT-TOKEN"; // Discord Bot Token
    
    // LOGIN FORM SUBMIT
    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_SESSION['userid'])) {
        $q = 'SELECT * FROM `users` WHERE username="'.$_POST['username'].'"';
        $qr = $conn->query($q);
        if ($qr->num_rows > 0) {
            while($row = $qr->fetch_assoc()) {
                if (password_verify($_POST['password'], $row['password'])) {
                    if ($row['verified'] == "true") {

                        $_SESSION['userid'] = $row['id'];
                        echo $_SESSION['userid'];
                        header("Location:https://feedback.beta.sharme.eu/");
                        
                    } else {
                        $error = "Your email adress is not verified.";
                    }
                } else {
                    $error = "Username or password is incorrect.";
                }
            }
        } else {
            $error = "Username or password is incorrect.";
        }
    }
    
    // SET LOGIN VAR
    if (isset($_SESSION['userid'])) {
        $q = 'SELECT * FROM `users` WHERE id='.$_SESSION['userid'];
        $qres = $conn->query($q);
        if ($qres->num_rows > 0) {
            while ($row = $qres->fetch_assoc()) {
                $username = $row['username'];
                $email = $row['email'];
                $avatar = $row['avatar'];
            }
        } else {
            unset($_SESSION['userid']);
        }
    }
    
    // LOGOUT
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
    }
    
    // FEEDBACK FORM SUBMIT
    if (isset($_POST['fusername']) && isset($_POST['femail']) && isset($_SESSION['userid'])) {
        $q = "SELECT * FROM `feedback` WHERE 1";
        $qr = $conn->query($q);
        
        // Add files in $arr
        $arr = array();
        if (isset($_FILES["fscreens"]) && isset($_FILES["fscreens"]["name"][0])) {
            foreach ($_FILES["fscreens"]["name"] as $i => $pImage) {
                if ($_FILES["fscreens"]["size"][$i] <= 20971520) {
                    $arr["images"][$i] = array(
                        "name" => $_FILES["fscreens"]["name"][$i],
                        "type" => $_FILES["fscreens"]["type"][$i],
                        "tmp_name" => $_FILES["fscreens"]["tmp_name"][$i],
                        "size" => $_FILES["fscreens"]["size"][$i]
                    );
                }
            }
        } else {
            $arr["images"] = [];
        }

        // Add all $_post fields in $arr
        foreach($_POST as $key => $val) {
            $arr[$key] = $val;
        }
        
        // Create files directory if there is attachments
        if (sizeof($arr['images']) > 0) mkdir("./uploads/feedbacks/".$qr->num_rows);
        
        $embarr = array();
        
        // Set Discord webhook array and move uploaded files
        foreach($arr['images'] as $im) {
            $embarr[] = '['.basename($im['name']).'](https://uploads.sharme.eu/feedbacks/'.$qr->num_rows.'/'.basename($im['name']).')';
            move_uploaded_file($im['tmp_name'], "./uploads/feedbacks/".$qr->num_rows."/".basename($im['name']));
        }
        
        // Set files embed array
        if (sizeof($arr['images']) > 0) $embeds = array(
            array(
                "title" => "Attachments / Pièces jointes",
                "color" => 3092790,
                "description" => join("\n", $embarr)
            )
        );
        else $embeds = array();
        
        // Save feedback in database
        $s = "INSERT INTO `feedback`(`id`, `type`, `lang`, `userid`, ".(($arr['fdiscord'] != "") ? "`discordtag`, " : "")."`text`". ((sizeof($arr['images']) > 0) ? ", `attachments`" : "") .") VALUES (".$qr->num_rows.", '".$arr['ftype']."', '".$arr['flang']."', ".$_SESSION['userid'].", ".(($arr['fdiscord'] != "") ? "'".$arr['fdiscord']."', " : "")."'".str_replace("'", "\\'", $arr['feedback'])."'". ((sizeof($arr['images']) > 0) ? ", 'https://uploads.sharme.eu/feedbacks/".$qr->num_rows."/'" : "") .")";
        $sr = $conn->query($s);
        
        // -------------- START -- Discord Webhook send
        $ch = curl_init();
        
        curl_setopt_array( $ch, [
            CURLOPT_URL => $webhookurl."?wait=true",
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode([
                "username" => "@".$username.(!empty($arr['fdiscord']) ? " (".$arr['fdiscord'].")" : ""),
                "avatar_url" => $avatar,
                "content" => ":flag_".
                    ($arr['flang'] == "en" ? "gb":"fr").
                    ": ".
                    strtoupper($arr['flang'])."\n".($arr['ftype'] == "suggestion" ? "**Suggestion**" : "**Bug report** / **Rapport de bug**").
                    "\n".$arr['feedback'],
                "embeds" => $embeds
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ]
        ]);
        
        $wres = curl_exec($ch);
        curl_close( $ch );

        // -------------- END -- Discord Webhook send
        
        // Return $arr with all post fields and files
        header("Content-Type:application/json");
        echo json_encode($arr);
        return;
    } else if (isset($_POST['fusername']) && isset($_POST['femail']) && !isset($_SESSION['userid'])) {
        // If form set but user not logged in return 401 Unauthorized
        header("Content-Type:application/json");
        echo json_encode(array(
            "error" => 401,
            "error_description" => "Unauthorized : you are not logged in."
        ));
        return;
    }
    
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Beta Feedback - SharMe</title>
        <link rel="icon" href="https://beta.sharme.eu/assets/images/sharme-o.png" type="image/png">
        <link rel="stylesheet" href="/style.css" type="text/css">
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    </head>
    <body class="h-100">
        <div class="popupbg" style="z-index:500;<?php if(isset($_SESSION['userid'])) echo 'display:none !important;'; ?>">
            <div id="loginpopup" class="p-2">
                <div class="closepopup position-absolute top-0 end-0 mt-2 me-2 cursor-pointer" title="Return to www.sharme.eu" onclick='window.location.href = "https://www.sharme.eu/"'><i class="bi bi-x-lg"></i></div>
                <h1 class="d-inline-block mb-2">Login required</h1>
                <p class="mb-3">Login with your SharMe Beta&trade; account</p>
                <form method="post" id="loginform" class="needs-validation was-validated" novalidate>
                    <div class="input-group has-validation mb-3">
                        <span class="input-group-text">@</span>
                        <input type="text" placeholder="Username" class="form-control" required minlength="3" name="username" <?php if(isset($_POST['username'])) echo 'value="'.$_POST['username'].'"' ; ?>>
                        <div class="invalid-feedback">Please enter your username.</div>
                    </div>
                    <div class="input-group has-validation mb-3">
                        <span class="input-group-text cursor-pointer" onclick="togglePassword()"><i id="passwordVisible" class="bi bi-eye-slash"></i></span>
                        <input type="password" placeholder="Password" class="form-control" required id="password" name="password" <?php if(isset($_POST['password'])) echo 'value="'.$_POST['password'].'"' ; ?>>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input cursor-not-allowed" id="receiveupdates" name="receiveupdates" disabled>
                        <label class="form-check-label cursor-not-allowed" for="receiveupdates">Receive feedbacks notifications</label>
                    </div>
                    <div class="mb-3 d-flex justify-content-center">
                        <button class="btn btn-primary w-100" type="submit">Login</button>
                    </div>
                    <div class="text-danger d-none text-center" <?php if(!empty($error)) echo 'style="display:block !important;"'; ?>><?php if(!empty($error)) echo $error; ?></div>
                </form>
            </div>
        </div>
        <div class="popupbg" id="spopup" style="z-index:500;display:none;">
            <div id="successpopup" class="p-2">
                <img class="d-block h-25" src="/check.gif" id="checkgif">
                <h1 class="d-inline-block mb-4">Thank you !</h1>
                <p class="fs-5">Your feedback have been saved successfully !</p>
                <button class="btn btn-success" onclick="window.location.reload(true)">Submit another feedback</button>
            </div>
        </div>
        
        <div class="popupbg" id="fpopup" style="z-index:500;display:none;">
            <div id="failurepopup" class="p-2">
                <img class="d-block h-25" src="/cross.gif" id="failgif">
                <h1 class="d-inline-block mb-4" id="failtitle">An error occured : </h1>
                <p class="fs-5" id="faildescr"></p>
                <button class="btn btn-danger mb-1" onclick='$("#fpopup")[0].style.display = "none"'>Close popup and retry</button><button class="btn btn-success mt-1" onclick="window.location.reload(true)">Refresh page</button>
            </div>
        </div>
        
        <main class="container p-5 d-flex flex-column justify-content-center align-items-center h-100" style="z-index:0;" id="main">
            
            <?php
            if (isset($_SESSION['userid'])) {
                echo '<div id="loggedas" class="d-block position-fixed top-0 start-0 bg-primary text-light p-2" style="border-bottom-right-radius:.25rem;user-select:none;">
                    Logged in as '.$username.'<br>
                    <div class="btn btn-primary w-100 p-1 mt-2 border border-info" onclick="logout()">Logout</div>
                </div>';
            }
            ?>
            
            <h1 class="text-center d-inline-block mb-4">Beta Feedback</h1>
            
            <form class="needs-validation w-50" novalidate id="mainform" enctype="multipart/form-data">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                    <input type="text" class="form-control" disabled name="fusername" <?php if(isset($username)) echo 'value="'.$username.'"' ; ?>>
                </div>
                <div class="input-group has-validation mb-3" id="discordgroup">
                    <span class="input-group-text"><i class="bi bi-discord"></i></span>
                    <input type="text" class="form-control" name="fdiscord" id="fdiscord" pattern="[^ ].{1,32}#[0-9]{4}">
                    <div class="invalid-feedback">Your discord username is invalid. It must be in the form example#0000</div>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" disabled name="femail" <?php if(isset($email)) echo 'value="'.$email.'"' ; ?>>
                </div>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text"><i class="bi bi-translate"></i></span>
                    <select class="form-select cursor-pointer" required name="flang" id="flang">
                        <option selected disabled value="">Feedback language</option>
                        <option value="fr">FR</option>
                        <option value="en">EN</option>
                    </select>
                    <div class="invalid-feedback">Please choose a language.</div>
                </div>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text"><i class="bi bi-chat-square-text"></i></span>
                    <select class="form-select cursor-pointer" required name="ftype" id="ftype">
                        <option selected disabled value="">Feedback type</option>
                        <option value="bugreport">Bug report</option>
                        <option value="suggestion">Suggestion</option>
                    </select>
                    <div class="invalid-feedback">Please select the feedback type.</div>
                </div>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text"><i class="bi bi-text-paragraph"></i></span>
                    <textarea class="form-control" placeholder="Explain precisely your feedback" required minlength="10" name="feedback"></textarea>
                    <div class="invalid-feedback">Please enter your feedback. 10 characters minimum.</div>
                </div>
                <div class="input-group has-validation mb-3">
                    <span class="input-group-text"><i class="bi bi-files-alt"></i></span>
                    <input type="button" class="form-control cursor-pointer text-start d-block" for="files" id="filesclick" value="Join screenshots (max. 5)" onclick='document.getElementById("files").click();'/>
                    <span class="input-group-text cursor-pointer bg-danger text-light d-none" id="clearFiles"><i class="bi bi-x-circle"></i></span>
                    <input class="d-none" type="file" id="files" multiple max="5" accept="image/*,video/*" name="fscreens"/>
                </div>
                <div id="fileList" class="d-flex flex-column mb-3 p-1 border border-secondary d-none rounded-1"></div>
                <div class="mb-3">
                    <button class="btn btn-primary w-100" type="submit" id="submitbt">Submit</button>
                </div>
            </form>
            <div class="position-fixed bottom-0 mb-2 text-primary" style="z-index:501;user-select: none;font-weight: bold;">Source code available <a target="_blank" href="https://github.com/SharMeSocial/feedback">here</a></div>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            
            // Post login form
            $('#loginform').submit(function(event) {
                event.preventDefault();
                if (!this.checkValidity()) {
                    event.stopPropagation();
                } else {
                    $.ajax({
                        url: window.location.pathname,
                        type: "POST",
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        success: function (result) {
                            window.location.reload(true);
                        }
                    });
                }
                form.classList.add("was-validated");
            })
            
            // Post feedback form
            $('#mainform').submit(function(event) {
                event.preventDefault();
                if (!this.checkValidity()) {
                    event.stopPropagation();
                } else {
                    if (!this.checkValidity() || $('#flang')[0].selectedIndex == 0 || $('#ftype')[0].selectedIndex == 0) {
                        event.stopPropagation();
                        $("#submitbt")[0].classList.toggle("btn-primary");
                        $("#submitbt")[0].classList.toggle("btn-danger");
                        setTimeout(() => {
                            $("#submitbt")[0].classList.toggle("btn-primary");
                            $("#submitbt")[0].classList.toggle("btn-danger");
                        }, 500);
                    } else {
                        $("#submitbt")[0].classList.toggle("btn-primary");
                        $("#submitbt")[0].classList.toggle("btn-success");
                        $("#submitbt")[0].disabled = "true";
                        $("#submitbt")[0].innerHTML = "<loader></loader>";
                        
                        var fdata = new FormData();
                        
                        // Disable fields and append each to formdata
                        Array.from(this.children).forEach(el => {
                            if (el.classList.contains("input-group")) {
                                Array.from(el.children).forEach(inp => {
                                    if (inp.nodeName == "INPUT" || inp.nodeName == "SELECT" || inp.nodeName == "TEXTAREA") {
                                        if (inp.type != "file") fdata.append(inp.name, inp.value);
                                        else {
                                            $.each(inp.files,function(j, file){
                                                fdata.append(inp.name+'['+j+']', file);
                                            });
                                        }
                                        inp.disabled = "true";
                                    }
                                })
                            }
                        });
                        
                        console.log(fdata);
                        
                        // POST Form
                        $.ajax({
                            url: window.location.pathname,
                            type: "POST",
                            data: fdata,
                            processData: false,
                            contentType: false,
                            success: function (result) {
                                // If no error show success popup
                                if (!result.error) {
                                    $("#spopup")[0].style.display = "block";
                                    $("#checkgif").attr("src", "/check.gif");
                                // Else show failure popup
                                } else {
                                    $("#fpopup")[0].style.display = "block";
                                    $("#failgif").attr("src", "/cross.gif");
                                    $("#failtitle")[0].innerText += String(" "+result.error);
                                    $("#faildescr")[0].innerText = result.error_description;
                                }
                            }
                        });
                    }
                }
                this.classList.add("was-validated");
            });
            
            // Show/Hide password on login form
            function togglePassword() {
                $("#passwordVisible")[0].classList.toggle("bi-eye-slash")
                $("#passwordVisible")[0].classList.toggle("bi-eye")
                
                if ($("#password")[0].type != "text") $("#password")[0].type = "text";
                else $("#password")[0].type = "password";
            }
            
            var filesinput = document.getElementById("files");
            
            // -------------- START -- Show uploaded files
            $("#files")[0].onchange = function(e) {
                $("#fileList")[0].innerHTML = "";
                
                $("#fileList")[0].classList.remove("d-none");
                $("#clearFiles")[0].classList.remove("d-none");
                
                var approvedHTML;
                var count=1;
                var files = e.currentTarget.files;

                for (var x in files) {
                    
                    var filesize = ((files[x].size/1024)/1024).toFixed(4);
                                                                                                   // ↓ MAX 20 Mb // ↓ Max 5 uploads //
                    if (files[x].name != "item" && typeof files[x].name != "undefined" && filesize <= 20 && count <= 5) { 
            
                        $("#fileList")[0].innerHTML += '<div class="d-flex w-100 flex-row"><span class="d-inline-block overflow-hidden text-break" style="max-width: 85%;text-overflow: ellipsis;white-space: nowrap;">'+files[x].name+'</span><span class="d-inline-block ms-auto text-secondary" style="max-width: 15%">'+bytesToSize(files[x].size)+"</span></div>";
            
                        count++;
                    } else if (files[x].name != "item" && typeof files[x].name != "undefined" && count <= 5) {
                        $("#fileList")[0].innerHTML += '<div class="d-flex w-100 flex-row"><span class="text-danger">The file "'+files[x].name+'" is over 20 MB ('+bytesToSize(files[x].size)+")</span></div>";
                    }
                }
            }
            // -------------- END -- Show uploaded files
            
            // Clear uploads
            $("#clearFiles")[0].onclick = function() {
                $("#files")[0].value = "";
                $("#fileList")[0].classList.add("d-none");
                $("#clearFiles")[0].classList.add("d-none");
            }
            
            // Convert bytes size to [00 Bytes/KB/MB/GB/TB]
            function bytesToSize(bytes) {
               var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
               if (bytes == 0) return '0 Byte';
               var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
               return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
            }
            
            var logged = <?= isset($_SESSION['userid']) ? "true" : "false"; ?>;
            
            // Refresh page if user remove/hide the login popup
            window.onload = function() {
                setInterval(() => {
                    if (logged == false && (!$("#loginpopup")[0] || $("#loginpopup")[0].style.display == "none") && !noreload) {
                        window.location.reload(true);
                        var noreload = true;
                    }
                }, 500)
            };
            
            // Logout post
            function logout() {
                $.ajax({
                    url: window.location.pathname,
                    type: "POST",
                    data: { "logout": "" },
                    success: function(response) {
                        window.location.reload(true);
                    }
                });
            }
            
            function prependClass(sel, strClass) {
                var $el = jQuery(sel);
            
                /* prepend class */
                var classes = $el.attr('class');
                classes = strClass +' ' +classes;
                $el.attr('class', classes);
            }
        </script>
    </body>
</html>
