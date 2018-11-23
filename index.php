<?php
$colors = array('purple', 'red', 'yellow', 'pink', 'indigo', 'blue', 'green');
$color_pick = array_rand($colors);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- Bootstrap Core CSS -->
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        
        <link href="assets/css/styles.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center pt-5">
                <div class="col-8 pt-3 pb-3 chat-wrapper">
                    <div id="message-box" class="w-100 mb-3"></div>
                    <div class="user-panel row">
                        <div class="col-3">
                            <input class="form-control" type="text" name="name" id="name" readonly />
                        </div>
                        <div class="col-7">
                            <input class="form-control" type="text" name="message" id="message" placeholder="Type your message here..." maxlength="100" />
                        </div>
                        <div class="col-2">
                            <button id="send-message" class="btn btn-success">Send <i class="fa fa-send"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal -->
        <div class="modal fade" id="nickname-modal" tabindex="-1" role="dialog" aria-labelledby="nickname-modal" aria-hidden="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Welcome!</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Please enter your nickname:</label>
                            <input class="form-control nickname" type="text" value="" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        
        <!-- Bootstrap Core JavaScript -->
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
        <script language="javascript" type="text/javascript">
            // Show welcome message and enter nickname modal
            $('#nickname-modal').modal({
                backdrop: 'static',
                show: true
            });
            
            $('#nickname-modal button').on('click', function() {
                select_nickname();
            });
            
            $(".nickname").on("keydown", function (event) {
                if (event.which == 13) {
                    select_nickname();
                }
            });
            
            function select_nickname() {
                var nickname = $('input.nickname').val();
                
                if ($.trim(nickname) === "") {
                    alert("Please enter your nickname.");
                } else {
                    $.getJSON('check-nickname.php', {nickname: nickname}, function(result) {
                        if (result.unique) {
                            $('#name').val(nickname);
                            $('#nickname-modal').modal('hide');
                            msgBox.append('<div class="system-message">Welcome ' + nickname + '!</div>'); // notify user
                        } else {
                            alert('Nickname ' + nickname + ' is already in use, please try another.');
                        }
                    });
                }
            }
            
            // create a new WebSocket object.
            var msgBox = $('#message-box'),
                wsUri = "ws://localhost:9000/chat/start-chat-server.php",
                websocket = new WebSocket(wsUri);
            
            // Message received from server
            websocket.onmessage = function (ev) {
                var response = JSON.parse(ev.data); //PHP sends Json data

                var res_type = response.type; //message type
                var user_message = response.message; //message text
                var user_name = response.name; //user name
                var user_color = response.color; //color

                switch (res_type) {
                    case 'user-message':
                        msgBox.append('<div><span class="user-name" style="color:' + user_color + '">' + user_name + '</span> : <span class="user-message">' + user_message + '</span></div>');
                        break;
                    case 'system':
                        msgBox.append('<div class="system-message">' + user_message + '</div>');
                        break;
                    case 'change-nick':
                        $('#name').val(user_message);
                        break;
                }
                
                // scroll to latest message
                msgBox[0].scrollTop = msgBox[0].scrollHeight;

            };

            websocket.onerror = function (ev) {
                msgBox.append('<div class="system-error">An error has occurred - ' + ev.data + '</div>');
            };
            
            websocket.onclose = function (ev) {
                msgBox.append('<div class="system-message">Connection closed.</div>');
            };

            //Message send button
            $('#send-message').click(function () {
                send_message();
            });

            //User hits enter key 
            $("#message").on("keydown", function (event) {
                if (event.which == 13) {
                    send_message();
                }
            });

            // send message
            function send_message() {
                var message_input = $('#message'); //user message text

                if (message_input.val() == "") { //empty name?
                    alert("Please enter your message");
                    return;
                }

                //prepare json data
                var msg = {
                    message: message_input.val(),
                    name: name_input.val(),
                    color: '<?php echo $colors[$color_pick]; ?>'
                };
                
                //convert and send data to server
                websocket.send(JSON.stringify(msg));
                message_input.val(''); //reset message input
            }
        </script>
    </body>
</html>