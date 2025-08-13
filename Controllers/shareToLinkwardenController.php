<?php
declare(strict_types=1);

final class FreshExtension_ShareToLinkwarden_Controller extends Minz_ActionController {
    public ?Minz_Extension $extension;

    /**
     * @var \ShareToLinkwarden\Models\View
     * @phpstan-ignore property.phpDocType
     */
    protected $view;

    public function __construct() {
        parent::__construct(\ShareToLinkwarden\Models\View::class);
    }

    public function init(): void {
        $this->extension = Minz_ExtensionManager::findExtension('Share to Linkwarden');
    }

    public function shareAction(): void {
        if (!FreshRSS_Auth::hasAccess()) {
            Minz_Error::error(403);
        }

        $id = Minz_Request::paramString('id');
        if ($id === '') {
            Minz_Error::error(404);
        }

        $entryDAO = FreshRSS_Factory::createEntryDao();
        $entry = $entryDAO->searchById($id);
        if ($entry === null) {
            Minz_Error::error(404);
            return;
        }
        $this->view->entry = $entry;

        Minz_View::prependTitle(_t('share_to_linkwarden.share.title') . ' · ');
        $this->view->_layout('simple');

        if (Minz_Request::isPost()) {
            $conf = FreshRSS_Context::userConf();
            $linkwardenUrl = $conf->linkwarden_url;
            $linkwardenToken = $conf->linkwarden_token;

            if (empty($linkwardenUrl) || empty($linkwardenToken)) {
                Minz_Request::bad(_t('share_to_linkwarden.share.feedback.not_configured'), [
                    'c' => 'shareToLinkwarden',
                    'a' => 'share',
                    'params' => ['id' => $id],
                ]);
                return;
            }

            $apiUrl = rtrim($linkwardenUrl, '/') . '/api/v1/links';
            $payload = json_encode(['url' => $entry->link()]);

            $headers = [
                'Authorization: Bearer ' . $linkwardenToken,
                'Content-Type: application/json',
            ];

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($responseCode >= 200 && $responseCode < 300) {
                Minz_Request::good(_t('share_to_linkwarden.share.feedback.sent'), [
                    'c' => 'index',
                    'a' => 'index',
                ]);
            } else {
                Minz_Request::bad(_t('share_to_linkwarden.share.feedback.failed'), [
                    'c' => 'shareToLinkwarden',
                    'a' => 'share',
                    'params' => ['id' => $id],
                ]);
            }
        }
    }
}