<?php
require_once 'slack_api.php';

function get_messages($slack, $options)
{
    $arr = $slack->call('channels.history', $options);
    $messages = $arr["messages"];
    $ret = array();
    for ($i = 0; $i < count($messages); ++$i) {
        $obj = $messages[$i];
        if (isset($obj["user"])) {
            $user = $obj["user"];
        }
        else {
            $user = "BOT_ID";
        }
        $ret[$i] = array("user" => $user, "message" => $obj["text"]);
    }
    return $ret;
}
function get_members($slack) {
    $profile = $slack->call('users.list');

    $members = $profile["members"];

    $users = array();
    for ($i=0; $i<count($members); ++$i) {
        $users[$members[$i]["id"]] = array( "name" => $members[$i]["name"], "real_name" => $members[$i]["real_name"]);
    }

    return $users;
}

function main_process() {
    if ( file_exists( 'access.txt' ) ) {
        $access_string = file_get_contents( 'access.txt' );
    } else {
        $access_string = '{}';
    }
    $access_data = json_decode( $access_string, true );
    $token = $access_data["access_token"];
    $Slack = new Slack_API($token);
    echo "<header><a href='/' class='link_main'>Главная страница</a><h2 class='msg-title'>Список сообщений из канала <span>".$access_data["incoming_webhook"]["channel"]."</span></h2></header>";
    $users = get_members($Slack);
    $options = array(
        "channel" => $access_data["incoming_webhook"]["channel_id"]
    );
    $messages = array_reverse(get_messages($Slack, $options));
    for ($i=0; $i<count($messages); ++$i) {

        $user = $messages[$i]["user"];

        if ($user == "BOT_ID") {
            $name = "This is app.";
            $real_name = "This is app.";
        }
        else {
            $name = $users[$user]["name"];
            $real_name = $users[$user]["real_name"];
        }

        echo "<div class='block_msg'><span>Никнейм: ".$name."</span><span>Имя: ".$real_name."
</span><span>Сообщение: ".$messages[$i]["message"]."</span></div>";
    }
}
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Slack Интерфейс</title>
    </head>
        <style>
            body {
                font-family: Helvetica, sans-serif;
                padding: 20px;
            }
            input {
                padding: 10px;
                font-size: 1.2em;
                width: 100%;
            }
            .wrapper_msgs {
                text-align: center;
                position: relative;
            }
            .block_msg {
                display: inline-block;
                background-color: #e1e5ec;
                border: grey 1px solid ;
                color: black;
                padding: 6px;
                width: 40%;
                margin-bottom: 10px;
                margin-right: 10px;
            }
            .block_msg span {
                display: block;
            }
            .msg-title span {
                color: darkblue;
            }
            .link_main {
                position: absolute;
                left: 0;
                top: 0;
                display: inline-block;
                background-color: lightgreen;
                color: black;
                padding: 6px;
                width: 14%;
                margin-bottom: 10px;
                margin-right: 10px;
                text-decoration: none;
            }
            .link_main:hover {
                background-color: mediumseagreen;
            }
            .msg-title {
                display: inline-block;

            }
        </style>
    <body>
        <div class="wrapper_msgs">
            <?php main_process(); ?>
        </div>
    </body>
</html>