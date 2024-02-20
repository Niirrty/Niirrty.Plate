<?php

namespace Niirrty\Plate;

enum CacheMode : string
{

    /**
     * Use the current defined cache settings.
     */
    case USER = 'user';

    /**
     * Ignore all cache settings and rebuild all required caches if a template is used.
     */
    case EDITOR = 'editor';

}