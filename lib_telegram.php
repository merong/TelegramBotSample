<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

/**
 * Performs a cURL request to a Telegram API and returns the parsed results.
 *
 * @param object Handle to cURL request.
 * @return object | bool Parsed response object or false on failure.
 */
function perform_telegram_request($handle) {
    if($handle === false) {
        Logger::error('Failed to prepare cURL handle', __FILE__);
        return false;
    }

    $response = perform_curl_request($handle);
    if($response === false) {
        return false;
    }

    // Everything fine, return the result as object
    $response = json_decode($response, true);
    return $response['result'];
}

/**
 * Prepares an API request using cURL.
 * Returns a cURL handle, ready to perform the request, or false on failure.
 *
 * @param string $url HTTP request URI.
 * @param string $method HTTP method ('GET' or 'POST').
 * @param array $parameters Query string parameters.
 * @param mixed $body String or array of values to be passed as request payload.
 * @return object | false cURL handle or false on failure.
 */
function prepare_curl_api_request($url, $method, $parameters = null, $body = null, $headers = null) {
    // Parameter checking
    if(!is_string($url)) {
        Logger::error('URL must be a string', __FILE__);
        return false;
    }
    if($method !== 'GET' && $method !== 'POST') {
        Logger::error('Method must be either GET or POST', __FILE__);
        return false;
    }
    if($method !== 'POST' && $body) {
        Logger::error('Cannot send request body content without POST method', __FILE__);
        return false;
    }
    if(!$parameters) {
        $parameters = array();
    }
    if(!is_array($parameters)) {
        Logger::error('Parameters must be an array of values', __FILE__);
        return false;
    }

    // Complex parameters (i.e., arrays) are encoded as JSON strings
    foreach ($parameters as $key => &$val) {
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }

    // Prepare final request URL
    $query_string = http_build_query($parameters);
    if(!empty($query_string)) {
        $url .= '?' . $query_string;
    }

    Logger::info("HTTP request to {$url}", __FILE__);

    // Prepare cURL handle
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_USERAGENT, 'Telegram Bot client, UWiClab (https://github.com/UWiClab/TelegramBotSample)');
    if($method === 'POST') {
        curl_setopt($handle, CURLOPT_POST, true);
        if($body) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
    }
    if(is_array($headers)) {
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    }

    return $handle;
}

/**
 * Retrieves information about the bot.
 * https://core.telegram.org/bots/api#getme
 *
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_get_bot_info() {
    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'getMe', 'GET', null, null);
    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot message.
 * https://core.telegram.org/bots/api#sendmessage
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $message Message to send.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_message($chat_id, $message, $parameters = null) {
    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'text' => $message
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendMessage', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot location message.
 * https://core.telegram.org/bots/api#sendlocation
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param float $latitude Coordinate latitude.
 * @param float $longitude Coordinate longitude.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_location($chat_id, $latitude, $longitude, $parameters = null) {
    if(!is_numeric($latitude) || !is_numeric($longitude)) {
        Logger:error('Latitude and longitude must be numbers', __FILE__);
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'latitude' => $latitude,
        'longitude' => $longitude
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendLocation', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot photo message.
 * https://core.telegram.org/bots/api#sendphoto
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $photo_path Relative path to the photo file to attach or full URI.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_photo($chat_id, $photo_path, $caption, $parameters = null) {
    if(!$photo_path) {
        Logger::error('Path to attached photo must be set', __FILE__);
        return false;
    }
    $is_remote = stripos($photo_path, 'http') === 0;
    if(!$is_remote && !file_exists($photo_path)) {
        Logger::error("Photo at local path {$photo_path} does not exist", __FILE__);
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'caption' => $caption
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendPhoto', 'POST', $parameters, array(
        'photo' => ($is_remote) ? $photo_path : new CURLFile($photo_path)
    ));

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram chat action update.
 * https://core.telegram.org/bots/api#sendchataction
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $action Type of action. See online API documentation.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_chat_action($chat_id, $action = 'typing') {
    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'action' => $action
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendChatAction', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Requests message updates from the Telegram API.
 * https://core.telegram.org/bots/api#getupdates
 *
 * @param int $offset Identifier of the first update to be returned, or null.
 * @param int $limit Maximum count of updates to fetch, or null.
 * @param int | bool $long_poll Performs a long polling request (defaults to false).
 *                              If true is passed, defaults to 60 seconds.
 *                              Otherwise, takes the timeout in seconds to wait.
 * @return array | false Parsed array of updates or false on failure.
 */
function telegram_get_updates($offset = null, $limit = null, $long_poll = false) {
    $parameters = array();
    if(is_numeric($offset))
        $parameters['offset'] = $offset;
    if(is_numeric($limit) && $limit > 0)
        $parameters['limit'] = $limit;
    if($long_poll === true)
        $long_poll = 60;
    if(is_numeric($long_poll) && $long_poll > 0)
        $parameters['timeout'] = $long_poll;

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'getUpdates', 'GET', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Edits a past text message.
 * https://core.telegram.org/bots/api#updating-messages
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param int $message_id Identifier of the existing chat message.
 * @param string $message Replacement message.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_edit_message($chat_id, $message_id, $text, $parameters = null) {
    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'editMessageText', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}
