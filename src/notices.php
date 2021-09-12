<?php

namespace BulkMetaEditor;

class Notices 
{
    const OPTION_NAME = 'arva_bme_notices';

    public static function get()
    {
        $option = get_option(self::OPTION_NAME);
        echo '<div class="notice '. $option['error_type'] .' '. $option['dismissible'] .'">';
        echo '<p>'. $option['message'] .'</p>';
        echo '</div>';
    }

    public static function set($message, $error_type, $dismissible = true)
    {
        update_option(self::OPTION_NAME, [
            'message' => $message,
            'error_type' => $error_type,
            'dismissible' => ($dismissible) ? 'is-dismissible' : null,
        ]);
    }
}