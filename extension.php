<?php
declare(strict_types=1);

final class ShareToLinkwardenExtension extends Minz_Extension {
    public function init(): void {
        $this->registerTranslates();

        $this->registerController('shareToLinkwarden');
        $this->registerViews();

        $conf = FreshRSS_Context::userConf();
        $linkwarden_url = $conf->linkwarden_url;
        $linkwarden_token = $conf->linkwarden_token;
        if (!empty($linkwarden_url) && !empty($linkwarden_token)) {
            FreshRSS_Share::register([
                'type' => 'linkwarden',
                'url' => Minz_Url::display(['c' => 'shareToLinkwarden', 'a' => 'share']) . '&id=~ID~',
                'transform' => [],
                'form' => 'simple',
                'method' => 'GET',
            ]);
        }

        spl_autoload_register(array($this, 'loader'));
    }

    public function handleConfigureAction(): void {
        $this->registerTranslates();

        if (Minz_Request::isPost()) {
            $conf = FreshRSS_Context::userConf();
            $conf->linkwarden_url = Minz_Request::paramString('linkwarden_url');
            $conf->linkwarden_token = Minz_Request::paramString('linkwarden_token');
            $conf->save();
        }
    }

    public function loader(string $class_name): void {
        if (strpos($class_name, 'ShareToLinkwarden') === 0) {
            $class_name = substr($class_name, 18);
            $base_path = $this->getPath() . '/';
            include($base_path . str_replace('\\', '/', $class_name) . '.php');
        }
    }
}