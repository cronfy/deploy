<?php

namespace Deploy;

class Deploy {

    public static function init() {
        // Recipe include path
        set_include_path(__DIR__ . '/../' . PATH_SEPARATOR . get_include_path());
    }

}
