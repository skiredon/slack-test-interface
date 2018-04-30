<?php

namespace App\SlackAPI;

/**
 * Simple abstraction of Slack API
 *
 * Uses curl, if not falls back to file_get_contents and HTTP stream.
 *
 * For all api methods, refer to https://api.slack.com/
 *
 * @author  Yong Zhen <yz@stargate.io>
 * @version  1.0.0
 */
class Slack_API {
    private $api_token;
    private $api_endpoint = 'https://slack.com/api/<method>';

    function __construct($api_token){
        $this->api_token = $api_token;
    }

    public function call($method, $args = array(), $timeout = 10){
        return $this->request($method, $args, $timeout);
    }

    private function request($method, $args = array(), $timeout = 10)
    {
        $url = str_replace('<method>', $method, $this->api_endpoint);
        $args['token'] = $this->api_token;
        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $post_data = http_build_query($args);
            $result = file_get_contents($url, false, stream_context_create(array(
                'http' => array(
                    'protocol_version' => 1.1,
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\nContent-length: " . strlen($post_data) . "\r\nConnection: close\r\n",
                    'content' => $post_data
                ),
            )));
        }
        return $result ? json_decode($result, true) : false;
    }
}