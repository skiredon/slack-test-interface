<?php

define( 'SLACK_CLIENT_ID', '' );
define( 'SLACK_CLIENT_SECRET', '' );
define ('SLACK_REDIRECT_URI', 'https://ciframe.com/test/skiredon/index.php?action=oauth');

require_once 'slack-main.php';
require_once 'slack-access.php';
require_once 'slack-api-exception.php';

require_once 'slack_api.php';

function initialize_slack_interface() {

    if ( file_exists( 'access.txt' ) ) {
        $access_string = file_get_contents( 'access.txt' );
    } else {
        $access_string = '{}';
    }

    $access_data = json_decode( $access_string, true );
    $slack = new Slack( $access_data );
    return $slack;
}

function do_action( $slack, $action ) {
    $result_message = '';
    switch ( $action ) {
        // Handles the OAuth callback by exchanging the access code to
        // a valid token and saving it in a file
        case 'oauth':
            $code = $_GET['code'];
            // Exchange code to valid access toke
            try {
                $access = $slack->do_oauth( $code );
                if ( $access ) {
                    file_put_contents( 'access.txt', $access->to_json() );
                    $result_message = 'Приложение успешно добавлено в Slack канал';
                }
            } catch ( Slack_API_Exception $e ) {
                $result_message = $e->getMessage();
            }
            break;
        case 'send_notification':
            $def_message = 'Hello!';
            $message = isset($_REQUEST['text']) ? $_REQUEST['text'] : $def_message;
            try {
                $slack->send_notification($message);
                $result_message = 'Сообщение отправлено в Slack канал';
            }
            catch (Slack_API_Exception $e) {
                $result_message = $e->getMessage();
            }
            break;
        case 'new_integrate':
			$access_string = file_get_contents('access.txt');
			$access_data = json_decode( $access_string, true );
			$token_ = $access_data["access_token"];
			$args = array("token" => $token_);
			$Slack = new Slack_API($token_);
            $response = $Slack->call('auth.revoke', $args);
            file_put_contents( 'access.txt', '' );
            header('Location: '.$_SERVER['REQUEST_URI']);
            break;
        default:
            break;
    }
    return $result_message;
}

$slack = initialize_slack_interface();
$result_message = '';
if ( isset( $_REQUEST['action'] ) ) {
    $action = $_REQUEST['action'];
    $result_message = do_action( $slack, $action );
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
                width: 50%;
                margin: 0 auto;
                font-size: 16px;
            }
            h1 {
                text-align: center;
            }
            .notification {
                padding: 20px;
                background-color: #fafad2;
            }
            input[type="text"] {
                padding: 5px;
                height: 40px;
                width: 80%;
                margin-right: 2%;
                font-size: 16px;
            }
            .block {
                background-color: #e1e5ec;
                border: #4D5662 1px solid ;
            }
            button {
                padding: 10px;
                min-width: 118px;
                background-color: lavender;
                height: 40px;
                border: none;
                font-size: 16px;
            }
            button:hover {
                background-color: #e1e5ec;
                cursor: pointer;
            }
            .form-msg {
                text-align: center;
            }
            .btn-msg {
                width: 100%;
            }
            .auth-slack-wrap {
                text-align: center;
            }
        </style>
    <body>
        <h1>Slack Интерфейс</h1>
        <?php if ( $result_message ) : ?>
            <p class="notification">
                <?php echo $result_message; ?>
            </p>
        <?php endif; ?>
        <?php if ( $slack->is_authenticated() ) : ?>
            <form action="" method="post">
                <input type="hidden" name="action" value="send_notification"/>
                <input class="form_input" type="text" name="text" placeholder="Введите сообщение для отправки в Slack" />
                <button type="submit">Отправить</button>
            </form>
            <form class="form-msg" action="/test/skiredon/messages.php" method="get">
                <button class="btn-msg" type="submit">Получить сообщения</button>
            </form>
            <form class="form-msg" action="index.php" method="post">
                <input type="hidden" name="action" value="new_integrate"/>
                <button class="btn-msg" type="submit">Новая интеграция</button>
            </form>
            <?php else : ?>
                <div class="auth-slack-wrap">
                    <a href="https://slack.com/oauth/authorize?client_id=<?php echo $slack->get_client_id(); ?>&scope=incoming-webhook,channels:history,
                    channels:read,users:read&redirect_uri=<?php echo SLACK_REDIRECT_URI?>">
                    <img alt="Add to Slack" height="40" width="139" 
                    src="https://platform.slack-edge.com/img/add_to_slack.png" 
                    srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, 
                    https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>
                </div>
        <?php endif; ?>
    </body>
</html>