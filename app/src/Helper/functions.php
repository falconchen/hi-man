<?php

/**
 * Description:一些有用的 function, 部分移植自wordpress
 * Author: falcon
 * Date: 2019/11/15
 * Time: 11:27 PM.
 */



/**
 * just a test.
 *
 * @param string $name
 */
function hi($name = 'name')
{
    echo "hi {$name}";
}

function trimSlug($slug) {
    $slug = strtolower(urlencode(str_replace('/','',trim($slug))));
    return trim(preg_replace('#[^a-z0-9\-]#','-',$slug),'-');


}


function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '')
{
    return update_metadata('user', $user_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Update metadata for the specified object. If no value already exists for the specified object
 * ID and metadata key, the metadata will be added.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
 * @param int    $object_id  ID of the object metadata is for
 * @param string $meta_key   Metadata key
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. If specified, only update existing metadata entries with
 *                           the specified value. Otherwise, update all entries.
 *
 * @return int|bool meta ID if the key didn't exist, true on successful update, false on failure
 */
function update_metadata($meta_type, $object_id, $meta_key, $meta_value, $prev_value = '')
{
    if (!$meta_type || !$meta_key || !is_numeric($object_id)) {
        return false;
    }

    $object_id = absint($object_id);
    if (!$object_id) {
        return false;
    }

    $table = _get_meta_table($meta_type);
    if (!$table) {
        return false;
    }

    $column = sanitize_key($meta_type . '_id');
    $id_column = 'user' == $meta_type ? 'umeta_id' : 'meta_id';

    // expected_slashed ($meta_key)
    $raw_meta_key = $meta_key;
    $meta_key = wp_unslash($meta_key);
    $passed_value = $meta_value;
    $meta_value = wp_unslash($meta_value);
    $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type);

    /**
     * Filters whether to update metadata of a specific type.
     *
     * The dynamic portion of the hook, `$meta_type`, refers to the meta
     * object type (comment, post, or user). Returning a non-null value
     * will effectively short-circuit the function.
     *
     * @since 3.1.0
     *
     * @param bool|null $check      whether to allow updating metadata for the given type
     * @param int       $object_id  object ID
     * @param string    $meta_key   meta key
     * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
     * @param mixed     $prev_value Optional. If specified, only update existing
     *                              metadata entries with the specified value.
     *                              Otherwise, update all entries.
     */
    $check = apply_filters("update_{$meta_type}_metadata", null, $object_id, $meta_key, $meta_value, $prev_value);
    if (null !== $check) {
        return (bool) $check;
    }

    // Compare existing value to new value if no prev value given and the key exists only once.
    if (empty($prev_value)) {
        $old_value = get_metadata($meta_type, $object_id, $meta_key);
        if (count($old_value) == 1) {
            if ($old_value[0] === $meta_value) {
                return false;
            }
        }
    }

    $meta_ids = $wpdb->get_col($wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id));
    if (empty($meta_ids)) {
        return add_metadata($meta_type, $object_id, $raw_meta_key, $passed_value);
    }

    $_meta_value = $meta_value;
    $meta_value = maybe_serialize($meta_value);

    $data = compact('meta_value');
    $where = array($column => $object_id, 'meta_key' => $meta_key);

    if (!empty($prev_value)) {
        $prev_value = maybe_serialize($prev_value);
        $where['meta_value'] = $prev_value;
    }

    foreach ($meta_ids as $meta_id) {
        /*
         * Fires immediately before updating metadata of a specific type.
         *
         * The dynamic portion of the hook, `$meta_type`, refers to the meta
         * object type (comment, post, or user).
         *
         * @since 2.9.0
         *
         * @param int    $meta_id    ID of the metadata entry to update.
         * @param int    $object_id  Object ID.
         * @param string $meta_key   Meta key.
         * @param mixed  $meta_value Meta value.
         */
        do_action("update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

        if ('post' == $meta_type) {
            /*
             * Fires immediately before updating a post's metadata.
             *
             * @since 2.9.0
             *
             * @param int    $meta_id    ID of metadata entry to update.
             * @param int    $object_id  Object ID.
             * @param string $meta_key   Meta key.
             * @param mixed  $meta_value Meta value.
             */
            do_action('update_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
        }
    }

    $result = $wpdb->update($table, $data, $where);
    if (!$result) {
        return false;
    }

    wp_cache_delete($object_id, $meta_type . '_meta');

    foreach ($meta_ids as $meta_id) {
        /*
         * Fires immediately after updating metadata of a specific type.
         *
         * The dynamic portion of the hook, `$meta_type`, refers to the meta
         * object type (comment, post, or user).
         *
         * @since 2.9.0
         *
         * @param int    $meta_id    ID of updated metadata entry.
         * @param int    $object_id  Object ID.
         * @param string $meta_key   Meta key.
         * @param mixed  $meta_value Meta value.
         */
        do_action("updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value);

        if ('post' == $meta_type) {
            /*
             * Fires immediately after updating a post's metadata.
             *
             * @since 2.9.0
             *
             * @param int    $meta_id    ID of updated metadata entry.
             * @param int    $object_id  Object ID.
             * @param string $meta_key   Meta key.
             * @param mixed  $meta_value Meta value.
             */
            do_action('updated_postmeta', $meta_id, $object_id, $meta_key, $meta_value);
        }
    }

    return true;
}

function maybe_serialize($data)
{
    if (is_array($data) || is_object($data)) {
        return serialize($data);
    }

    // Double serialization is required for backward compatibility.
    // See https://core.trac.wordpress.org/ticket/12930
    // Also the world will end. See WP 3.6.1.
    if (is_serialized($data, false)) {
        return serialize($data);
    }

    return $data;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @since 2.0.5
 *
 * @param string $data   Value to check to see if was serialized.
 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
 * @return bool False if not serialized and true if it was.
 */
function is_serialized( $data, $strict = true ) {
	// if it isn't a string, it isn't serialized.
	if ( ! is_string( $data ) ) {
		return false;
	}
	$data = trim( $data );
 	if ( 'N;' == $data ) {
		return true;
	}
	if ( strlen( $data ) < 4 ) {
		return false;
	}
	if ( ':' !== $data[1] ) {
		return false;
	}
	if ( $strict ) {
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) {
			return false;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );
		// Either ; or } must exist.
		if ( false === $semicolon && false === $brace )
			return false;
		// But neither must be in the first X characters.
		if ( false !== $semicolon && $semicolon < 3 )
			return false;
		if ( false !== $brace && $brace < 4 )
			return false;
	}
	$token = $data[0];
	switch ( $token ) {
		case 's' :
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
			} elseif ( false === strpos( $data, '"' ) ) {
				return false;
			}
			// or else fall through
		case 'a' :
		case 'O' :
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b' :
		case 'i' :
		case 'd' :
			$end = $strict ? '$' : '';
			return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	}
	return false;
}
/**
 * Unserialize value only if it was serialized.
 *
 * @since 2.0.0
 *
 * @param string $original Maybe unserialized original, if is needed.
 * @return mixed Unserialized data can be any type.
 */
function maybe_unserialize( $original ) {
	if ( is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
		return @unserialize( $original );
	return $original;
}


/**
 * Generate a random UUID (version 4).
 *
 * @since 4.7.0
 *
 * @return string UUID
 */
function wp_generate_uuid4()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function hi_random()
{
    return sprintf(
        '%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/**
 * 檢查一個日期是否有效
 * ref: https://www.php.net/manual/zh/function.checkdate.php.
 */
//用法
// var_dump(validateDate('2012-02-28 12:12:12')); # true
// var_dump(validateDate('2012-02-30 12:12:12')); # false
// var_dump(validateDate('2012-02-28', 'Y-m-d')); # true
// var_dump(validateDate('28/02/2012', 'd/m/Y')); # true
// var_dump(validateDate('30/02/2012', 'd/m/Y')); # false
// var_dump(validateDate('14:50', 'H:i')); # true
// var_dump(validateDate('14:77', 'H:i')); # false
// var_dump(validateDate(14, 'H')); # true
// var_dump(validateDate('14', 'H')); # true

// var_dump(validateDate('2012-02-28T12:12:12+02:00', 'Y-m-d\TH:i:sP')); # true
// # or
// var_dump(validateDate('2012-02-28T12:12:12+02:00', DateTime::ATOM)); # true

// var_dump(validateDate('Tue, 28 Feb 2012 12:12:12 +0200', 'D, d M Y H:i:s O')); # true
// # or
// var_dump(validateDate('Tue, 28 Feb 2012 12:12:12 +0200', DateTime::RSS)); # true
// var_dump(validateDate('Tue, 27 Feb 2012 12:12:12 +0200', DateTime::RSS)); # false
// # ...

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);

    return $d && $d->format($format) == $date;
}

/**
 * Retrieves a modified URL query string.
 *
 * You can rebuild the URL and append query variables to the URL query by using this function.
 * There are two ways to use this function; either a single key and value, or an associative array.
 *
 * Using a single key and value:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Using an associative array:
 *
 *     add_query_arg( array(
 *         'key1' => 'value1',
 *         'key2' => 'value2',
 *     ), 'http://example.com' );
 *
 * Omitting the URL from either use results in the current URL being used
 * (the value of `$_SERVER['REQUEST_URI']`).
 *
 * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
 *
 * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
 *
 * Important: The return value of add_query_arg() is not escaped by default. Output should be
 * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
 * (XSS) attacks.
 *
 * @since 1.5.0
 *
 * @param string|array $key   Either a query variable key, or an associative array of query variables.
 * @param string       $value Optional. Either a query variable value, or a URL to act upon.
 * @param string       $url   Optional. A URL to act upon.
 * @return string New URL query string (unescaped).
 */
function add_query_arg()
{
    $args = func_get_args();
    if (is_array($args[0])) {
        if (count($args) < 2 || false === $args[1])
            $uri = $_SERVER['REQUEST_URI'];
        else
            $uri = $args[1];
    } else {
        if (count($args) < 3 || false === $args[2])
            $uri = $_SERVER['REQUEST_URI'];
        else
            $uri = $args[2];
    }

    if ($frag = strstr($uri, '#'))
        $uri = substr($uri, 0, -strlen($frag));
    else
        $frag = '';

    if (0 === stripos($uri, 'http://')) {
        $protocol = 'http://';
        $uri = substr($uri, 7);
    } elseif (0 === stripos($uri, 'https://')) {
        $protocol = 'https://';
        $uri = substr($uri, 8);
    } else {
        $protocol = '';
    }

    if (strpos($uri, '?') !== false) {
        list($base, $query) = explode('?', $uri, 2);
        $base .= '?';
    } elseif ($protocol || strpos($uri, '=') === false) {
        $base = $uri . '?';
        $query = '';
    } else {
        $base = '';
        $query = $uri;
    }

    wp_parse_str($query, $qs);
    $qs = urlencode_deep($qs); // this re-URL-encodes things that were already in the query string
    if (is_array($args[0])) {
        foreach ($args[0] as $k => $v) {
            $qs[$k] = $v;
        }
    } else {
        $qs[$args[0]] = $args[1];
    }

    foreach ($qs as $k => $v) {
        if ($v === false)
            unset($qs[$k]);
    }

    $ret = http_build_query($qs);
    $ret = trim($ret, '?');
    $ret = preg_replace('#=(&|$)#', '$1', $ret);
    $ret = $protocol . $base . $ret . $frag;
    $ret = rtrim($ret, '?');
    return $ret;
}

/**
 * Navigates through an array, object, or scalar, and encodes the values to be used in a URL.
 *
 * @since 2.2.0
 *
 * @param mixed $value The array or string to be encoded.
 * @return mixed $value The encoded value.
 */
function urlencode_deep($value)
{
    return map_deep($value, 'urlencode');
}


/**
 * Parses a string into variables to be stored in an array.
 *
 * Uses {@link https://secure.php.net/parse_str parse_str()} and stripslashes if
 * {@link https://secure.php.net/magic_quotes magic_quotes_gpc} is on.
 *
 * @since 2.2.1
 *
 * @param string $string The string to be parsed.
 * @param array  $array  Variables will be stored in this array.
 */
function wp_parse_str($string, &$array)
{
    parse_str($string, $array);

    return $array;
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 *
 * @since 2.0.0
 *
 * @param mixed $value The value to be stripped.
 * @return mixed Stripped value.
 */
function stripslashes_deep($value)
{
    return map_deep($value, 'stripslashes_from_strings_only');
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 *
 * This is similar to `array_walk_recursive()` but acts upon objects too.
 *
 * @since 4.4.0
 *
 * @param mixed    $value    The array, object, or scalar.
 * @param callable $callback The function to map onto $value.
 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
 */
function map_deep($value, $callback)
{
    if (is_array($value)) {
        foreach ($value as $index => $item) {
            $value[$index] = map_deep($item, $callback);
        }
    } elseif (is_object($value)) {
        $object_vars = get_object_vars($value);
        foreach ($object_vars as $property_name => $property_value) {
            $value->$property_name = map_deep($property_value, $callback);
        }
    } else {
        $value = call_user_func($callback, $value);
    }

    return $value;
}

/**
 * Callback function for `stripslashes_deep()` which strips slashes from strings.
 *
 * @since 4.4.0
 *
 * @param mixed $value The array or string to be stripped.
 * @return mixed $value The stripped value.
 */
function stripslashes_from_strings_only($value)
{
    return is_string($value) ? stripslashes($value) : $value;
}

/**
 * Removes an item or items from a query string.
 *
 * @since 1.5.0
 *
 * @param string|array $key   Query key or keys to remove.
 * @param bool|string  $query Optional. When false uses the current URL. Default false.
 * @return string New URL query string.
 */
function remove_query_arg($key, $query = false)
{
    if (is_array($key)) { // removing multiple keys
        foreach ($key as $k)
            $query = add_query_arg($k, false, $query);
        return $query;
    }
    return add_query_arg($key, false, $query);
}



/**
 * PHP stdClass Object转array  
 *
 * @param [type] $array
 * @return 
 */
function object2array($object)
{
    $arr =  json_decode(json_encode($object), true);
    return  $arr;
}


function getMetaValue($model, $id, $key)
{
    // switch ($model) {
    //     case :'PostMeta'
    //         $idName = 'post_id';
    //         break;
    // }

    $modelName = class_basename($model);
    switch ($modelName) {
        case 'PostMeta':
            $idName = 'post_id';
            break;
        case 'UserMeta':
            $idName = 'user_id';
            break;
            //.... others more
        default:
            return null;
    }

    $data = (new $model())->where($idName, $id)->where('meta_key', $key)->first();

    if (!is_null($data)) {
        $data = unserialize($data->meta_value);
        if (is_object($data)) {
            $data = object2array($data);
        }
    }
    return $data;
}

function getPostMeta($postId, $key)
{
    // $oscSyncResult = PostMeta::where('post_id', 16)->where('meta_key', 'osc_sync_result')->first();
    // if (!is_null($oscSyncResult)) {
    //     $oscSyncResult = unserialize($oscSyncResult->meta_value);
    //     if (is_object($oscSyncResult)) {
    //         $oscSyncResult = object2array($oscSyncResult);
    //     }
    // }
    return getMetaValue('App\Model\PostMeta', $postId, $key);
}

function getUserMeta($userId, $key)
{
    // $userOscMeta = UserMeta::where('user_id', 16)->where('meta_key', 'osc_userinfo')->first();
    // if (!is_null($userOscMeta)) {
    //     $userOsc = unserialize($userOscMeta->meta_value);
    //     echo $userOsc['homepage'] . '/blog/' . $oscSyncResult['result']['id'];
    // }
    return getMetaValue('App\Model\UserMeta', $userId, $key);
}

/**
 * 获取同步到osc后的链接
 *
 * @param int $postId
 * @param integer $postAuthor  //提供时可减少查询
 * @return null | string
 */
function getOscPostLink($postId, $postAuthor = 0)
{
    $link = null;
    $oscId = getOscPostId($postId);
    if ($oscId) {
        if ($postAuthor == 0) {
            $post = App\Model\Post::where('post_id', $postId)->first();
            $postAuthor = $post->post_author;
        }
        $oscUserInfo = getUserMeta($postAuthor, 'osc_userinfo');
        if (isset($oscUserInfo['homepage'])) {
            $link = $oscUserInfo['homepage'] . '/blog/' . $oscId;
        }
    }
    return $link;
}

function getOscTweetLink($postAuthor,$oscTweetId) {

    $link = null;
    $oscUserInfo = getUserMeta($postAuthor, 'osc_userinfo');
    if (isset($oscUserInfo['homepage'])) {
        $link = $oscUserInfo['homepage'] . '/tweet/' . $oscTweetId;
    }
    return $link;
}

/**
 * 获取同步后的osc 文章id
 *
 * @param [int] $postId
 * @return int | 0
 */
function getOscPostId($postId)
{
    $oscSyncResult = getPostMeta($postId, 'osc_sync_result');
    return isset($oscSyncResult['result']['id']) ? intval($oscSyncResult['result']['id']) : 0;
}

/**
 * replace url text to archor elment
 *
 * @param string $text
 * @return string
 */
function makeLinks($text,$target="_blank")
{     
  if( preg_match_all('|<a[^>]+href=(.*?)[^>]*>(.*?)</a>|is',$text,$matches) ){
      $find = $matches[0];
      $tmpKeys = array_map(function($str){ return base64_encode($str);},$find);
      $text = str_replace($find,$tmpKeys,$text);
  }

  $text =strip_tags($text);  
  $text = App\Helper\Utils::make_links_blank($text);
  if(isset($find)) {
      return str_replace($tmpKeys,$find,$text);
  }
  return $text;

}

function replaceTweetTopic($text,$to='##') {

    return str_replace('https://www.oschina.net/tweet-topic/',$to,$text);
}



