<!DOCTYPE html>

<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="assets/js/jquery-3.3.1.min.js"></script>
    <script src="assets/js/js.cookie.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script id="message_template" type="text/x-custom-template">
        <div>
            <div class="col-lg-12">
                <div>User: <span class="user"></span></div>
                <div>Message: <span class="message"></span></div>
                <p class="new-message rounded bg-danger text-white text-center">new</p>
                <hr>
            </div>
        </div>
    </script>
    <style>
        .message-list{padding-top: 50px;}
        .new-message {width: 60px; font-size: 15px;}
        .hidden{display: none;}
    </style>
</head>

<body>
<div class="container">
    <div class="row mt-4">
        <form class="login-form hidden" method="post" action="/">
            <div class="form-group">
                <label for="user">Your Name</label>
                <input id="user" name="user">
            </div>
            <div class="form-group">
                <input type="submit" value="Login" class="btn btn-primary">
            </div>
        </form>
        <form class="message-form hidden" method="post" action="/">
            <div class="form-group">
                <textarea id="message" name="text" cols="70" rows="5" class="rounded"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" value="Send Message" class="btn btn-success">
            </div>
        </form>
    </div>
    <div id="chat" class="message-list hidden row">
        <?php
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $history = $redis->lrange('chat', 0, -1);
        foreach($history as $message){
            $m = json_decode($message);
            echo '<div class="col-lg-12">
                    <div>User: <span class="user">'.$m->user.'</span></div>
                    <div>Message: <span class="message">'.$m->message.'</span></div>
                    <hr>
                  </div>';
        }
        ?>
    </div>
</div>
<script>

    function addMessage(data, template, chat){
        $('.new-message').remove();
        var m = $(template).clone();
        $(m).find('.user').html(data.user);
        $(m).find('.message').html(data.message);
        $(chat).prepend($(m).html());
    }
    function hideElem(){
        $('.login-form').addClass('hidden');
        $('.message-form').removeClass('hidden');
        $('.message-list').removeClass('hidden');
    }
    $(document).ready(function(){
        if(Cookies.get('user')){
            hideElem();

            var chat = $('#chat');
            var template = $('#message_template').html();
            var connect = new WebSocket('ws://localhost:8080');

            connect.onopen = function (event) {
                console.log("Connection established!");
            };

            connect.onmessage = function(e) {
                var data = JSON.parse(e.data);
                addMessage(data, template, chat);
            };
        }else{
            $('.login-form').removeClass('hidden');
        }

        $('.login-form').submit(function(e){
            e.preventDefault();

            var login =  $('#user').val();
            if(!Cookies.get('user')){
                Cookies.set('user', login, { expires: 7 });
            }
            location.reload();
        });

        $('.message-form').submit(function(e){
            e.preventDefault();

            var message = $('#message').val();
            if(message.length > 0){
                var data = {
                    'user': Cookies.get('user'),
                    'message': message
                };
                connect.send(JSON.stringify(data));
                $('#message').text('').val('');
                addMessage(data, template, chat);
            }
        });
    })
</script>
</body>
</html>