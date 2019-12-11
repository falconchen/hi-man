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
 * Generate a random UUID (version 4).
 *
 * @since 4.7.0
 *
 * @return string UUID
 */
function wp_generate_uuid4()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function hi_random()
{
    return sprintf('%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
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