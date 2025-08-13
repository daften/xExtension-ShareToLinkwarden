<?php
declare(strict_types=1);

namespace ShareToLinkwarden\Models;

class View extends \Minz_View {
    public ?\FreshRSS_Entry $entry = null;
    public string $rss_url = '';
    public string $rss_title = '';
}