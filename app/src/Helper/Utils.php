<?php
namespace App\Helper;

class Utils {

    public static function get_default_link_attribs( $regex_matches = [] ) {
        $t = ' target="_blank" ';
        return $t;
    }

    /**
     * self::set_protocol();
     * @param string $link
     * @return string
     */
    public static function set_protocol( $link ) {
        if ( ! preg_match( '#^https?#si', $link ) ) {
            $link = 'http://' . $link;
        }
        return $link;
    }

/**
     * Goes through text and makes whatever text that look like a link an html link
     * which opens in a new tab/window (by adding target attribute).
     * 
     * Usage: self::make_links_blank( $text );
     * 
     * @param str $text
     * @return str
     * @see http://stackoverflow.com/questions/1188129/replace-urls-in-text-with-html-links
     * @author Angel.King.47 | http://dashee.co.uk
     * @author Svetoslav Marinov (Slavi) | http://orbisius.com
     */
    public static function make_links_blank( $text ) {
        $patterns = [
            '#(?(?=<a[^>]*>.+?<\/a>)
                 (?:<a[^>]*>.+<\/a>)
                 |
                 ([^="\']?)((?:https?|ftp):\/\/[^<> \n\r]+)
             )#six' => function ( $matches ) {
                $r1 = empty( $matches[1] ) ? '' : $matches[1];
                $r2 = empty( $matches[2] ) ? '' : $matches[2];
                $r3 = empty( $matches[3] ) ? '' : $matches[3];

                $r2 = empty( $r2 ) ? '' : self::set_protocol( $r2 );
                $res = ! empty( $r2 ) ? "$r1<a href=\"$r2\">$r2</a>$r3" : $matches[0];
                $res = stripslashes( $res );

                return $res;
             },

            '#(^|\s)((?:https?://|www\.|https?://www\.)[^<>\ \n\r]+)#six' => function ( $matches ) {
                $r1 = empty( $matches[1] ) ? '' : $matches[1];
                $r2 = empty( $matches[2] ) ? '' : $matches[2];
                $r3 = empty( $matches[3] ) ? '' : $matches[3];

                $r2 = ! empty( $r2 ) ? self::set_protocol( $r2 ) : '';
                $res = ! empty( $r2 ) ? "$r1<a href=\"$r2\">$r2</a>$r3" : $matches[0];
                $res = stripslashes( $res );

                return $res;
            },

            // Remove any target attribs (if any)
            '#<a([^>]*)target="?[^"\']+"?#si' => '<a\\1',

            // Put the target attrib
            '#<a([^>]+)>#si' => '<a\\1 target="_blank">',

            // Make emails clickable Mailto links
            '/(([\w\-]+)(\\.[\w\-]+)*@([\w\-]+)
                (\\.[\w\-]+)*)/six' => function ( $matches ) {

                $r = $matches[0];
                $res = ! empty( $r ) ? "<a href=\"mailto:$r\">$r</a>" : $r;
                $res = stripslashes( $res );

                return $res;
            },
        ];

        foreach ( $patterns as $regex => $callback_or_replace ) {
            if ( is_callable( $callback_or_replace ) ) {
                $text = preg_replace_callback( $regex, $callback_or_replace, $text );
            } else {
                $text = preg_replace( $regex, $callback_or_replace, $text );
            }
        }

        return $text;
    }
}