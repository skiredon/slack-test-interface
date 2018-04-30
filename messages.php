<?php

namespace App\Messages;

error_reporting(E_ERROR | E_PARSE);


use App\SlackAPI\Slack_API;

require_once 'slack_api.php';

function pretty_json($json)
{
    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = "\t";
    $newLine = "\n";

    for ($i = 0; $i < $strLen; $i++) {
        // Grab the next character in the string.
        $char = $json[$i];

        // Are we inside a quoted string?
        if ($char == '"') {
            // search for the end of the string (keeping in mind of the escape sequences)
            if (!preg_match('`"(\\\\\\\\|\\\\"|.)*?"`s', $json, $m, null, $i)) {
                return $json;
            }

            // add extracted string to the result and move ahead
            $result .= $m[0];
            $i += strLen($m[0]) - 1;
            continue;
        } else if ($char == '}' || $char == ']') {
            $result .= $newLine;
            $pos--;
            $result .= str_repeat($indentStr, $pos);
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if ($char == ',' || $char == '{' || $char == '[') {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }

            $result .= str_repeat($indentStr, $pos);
        }

    }
    return $result;
}

function get_messages($slack, $options)
{
    $arr = $slack->call('channels.history', $options);
    $messages = $arr["messages"];
    $ret = array();
    for ($i = 0; $i < count($messages); ++$i) {
        $obj = $messages[$i];
        if (isset($obj["user"])) {
            $user = $obj["user"];
        } else {
            $user = "BOT_ID";
        }
        $ret[$i] = array("user" => $user, "message" => $obj["text"]);
    }
    return $ret;
}
function get_members($slack)
{
    $profile = $slack->call('users.list');

    $members = $profile["members"];

    $users = array();
    for ($i = 0; $i < count($members); ++$i) {
        $users[$members[$i]["id"]] = array("name" => $members[$i]["name"], "real_name" => $members[$i]["real_name"]);
    }

    return $users;
}

function main_process($token, $access_data)
{
    $Slack = new Slack_API($token);
    $users = get_members($Slack);
    $options = array(
        "channel" => $access_data["incoming_webhook"]["channel_id"]
    );
    $messages = array_reverse(get_messages($Slack, $options));

    for ($i = 0; $i < count($messages); ++$i) {

        if ($i < 100) {
            $user = $messages[$i]["user"];

            if ($user == "BOT_ID") {
                $name = "This is app.";
                $real_name = "This is app.";
            } else {
                $name = $users[$user]["name"];
                $real_name = $users[$user]["real_name"];
            }

            echo "<div class='block_msg'><span>Никнейм: " . $name . "</span><span>Имя: " . $real_name . "
            </span><span>Сообщение: " . $messages[$i]["message"] . "</span></div>";
        }
    }
}

function to_json($token, $access_data)
{
    if (file_exists('messages.json')) {
        #$json_ = file_get_contents('messages.json');
        file_put_contents('messages.json', '');
    } else {
        $json_ = array();
    }

    $Slack = new Slack_API($token);
    $users = get_members($Slack);
    $options = array(
        "channel" => $access_data["incoming_webhook"]["channel_id"],
        "count" => 400
    );
    $messages = array_reverse(get_messages($Slack, $options));

    for ($i = 0; $i < count($messages); ++$i) {

        $user = $messages[$i]["user"];

        if ($user == "BOT_ID") {
            $name = "This is app.";
            $real_name = "This is app.";
        } else {
            $name = $users[$user]["name"];
            $real_name = $users[$user]["real_name"];
        }

        $msgs = $json_[$user]['messages'];

        if (isset($msgs)) {
            $msgs = array_merge($json_[$user]['messages'], array($messages[$i]["message"]));
        } else {
            $msgs = array($messages[$i]["message"]);
            #echo $msgs."<br>";
        }

        $json_[$user] = array("name" => $name, "real_name" => $real_name, "messages" => $msgs);
        file_put_contents('messages.json', json_encode($json_, JSON_PRETTY_PRINT));
    }

    return $json_;
}

if ( file_exists( 'access.txt' ) ) {
    $access_string = file_get_contents('access.txt');
} else {
    $access_string = '{}';
}
$access_data = json_decode( $access_string, true );
$token = $access_data["access_token"];
$json_data = to_json($token, $access_data);
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Slack Интерфейс</title>
        <link href="static/css/bootstrap.min.css" rel="stylesheet">
    </head>
        <style>
            html {
                overflow: -moz-scrollbars-vertical;
                overflow-y: scroll;
            }
            body {
                font-family: Helvetica, sans-serif;
                margin: 20px;
            }
            .root {
                padding: 10px;
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
				min-height: 100px;
				max-height: 100px;
				overflow-y: auto;
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
                display: inline-block;
                background-color: lightgreen;
                color: black;
                padding: 6px;
                width: 100%;
                margin-bottom: 10px;
                text-decoration: none;
                text-align: center;
				min-width: 150px;
            }
            a.link_main:focus, a.link_main:hover {
                background-color: mediumseagreen;
                text-decoration: none;!important;
            }
            .msg-title {
                text-align: center;
                margin-bottom: 35px;
            }
            .nav-tabs {
                margin-bottom: 10px;
            }
            .nav-tabs > li, .nav-pills > li {
                float:none;
                display:inline-block;
                *display:inline; /* ie7 fix */
                zoom:1; /* hasLayout ie7 trigger */
            }
            a:hover, a:focus {
                text-decoration: none;!important;
                background-color: limegreen;
            }
            .nav-tabs, .nav-pills {
                text-align:center;
            }
			.link_wrap {
				width: 20%;
				min-width: 200px;
				margin: 0 auto;
			}
			@media only screen and (max-width: 600px) {
				.block_msg {
					width: 100%;
				}
			}
        </style>
    <body>
        <div class="root">
            <header>
                <h2 class='msg-title'>Список сообщений из канала
                    <span>
                        <?php echo $access_data["incoming_webhook"]["channel"]?>
                    </span>
                </h2>
                <div class="link_wrap">
					<a href=. class='link_main'>Главная страница</a>
				</div>
                </header>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#tab-one" aria-controls="home" role="tab" data-toggle="tab">Вид 1</a></li>
                <li role="presentation"><a href="#tab-two" aria-controls="profile" role="tab" data-toggle="tab">Вид 2</a></li>
            </ul>
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="tab-one">
                    <h2>Данные также сохранены в файле <i>"messages.json"</i></i></h2>
                    <pre>
                        <?php echo pretty_json(json_encode($json_data)) ?>
                    </pre>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab-two">
                    <div class="wrapper_msgs">
                        <?php main_process($token, $access_data); ?>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <!-- Include all compiled plugins (below), or include individual files as needed -->
        <script src="js/bootstrap.min.js"></script>
    </body>
</html>