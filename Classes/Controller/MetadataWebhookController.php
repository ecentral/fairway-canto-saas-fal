<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Controller;

use Fairway\CantoSaasFal\Exception;
use Fairway\CantoSaasFal\Resource\Event\IncomingWebhookEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

final class MetadataWebhookController extends ActionController
{
    private ServerRequest $serverRequest;
    private ExtensionConfiguration $extensionConfiguration;

    public function __construct(ServerRequest $serverRequest, ExtensionConfiguration $extensionConfiguration)
    {
        $this->serverRequest = $serverRequest;
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function canProcessRequest(RequestInterface $request): bool
    {
        return $request instanceof Request && $request->getMethod() === 'POST';
    }

    public function indexAction(): string
    {
        $json = $this->getBody();
        $metadataToken = $this->extensionConfiguration->get('canto_saas_fal', 'metadata_hook_token');
        $assetVersionUpdate = $this->extensionConfiguration->get('canto_saas_fal', 'newversion_hook_token');
        $assetDeletion = $this->extensionConfiguration->get('canto_saas_fal', 'deletion_hook_token');

        switch ($json['secure_token']) {
            case $metadataToken:
                $type = IncomingWebhookEvent::METADATA_UPDATE;
                break;
            case $assetVersionUpdate:
                $type = IncomingWebhookEvent::ASSET_VERSION_UPDATE;
                break;
            case $assetDeletion:
                $type = IncomingWebhookEvent::ASSET_DELETION;
                break;
            default:
                $type = IncomingWebhookEvent::CUSTOM;
        }
        $event = IncomingWebhookEvent::fromJsonArray($json, $type);

        if ($type === IncomingWebhookEvent::CUSTOM) {
            $event->setToken($json['secure_token']);
        }

        $this->eventDispatcher->dispatch($event);

        try {
            return json_encode(['success' => true, 'type' => $type], JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            // currently silenced, canto webhooks dont evaluate the response either way @todo add logging
            return '';
        }
    }

    private function getBody(): array
    {
        try {
            $body = $this->serverRequest->getBody()->getContents();
            if (!$body) {
                throw new \Exception('The webhook does not contain any data.');
            }
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            throw new Exception('Could not perform any action on the webhook', 1638434149, $exception);
        }
    }
}
