<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Controller;

use Ecentral\CantoSaasFal\Exception;
use Ecentral\CantoSaasFal\Resource\Event\MetadataWebhookEvent;
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

    public function canProcessRequest(RequestInterface $request)
    {
        return $request instanceof Request && $request->getMethod() === 'POST';
    }

    public function indexAction()
    {
        try {
            $body = $this->serverRequest->getBody()->getContents();
            if (!$body) {
                throw new \Exception('The webhook does not contain any data.');
            }
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            throw new Exception('Could not perform any action on the webhook', 1638434149, $exception);
        }

        $metadataToken = $this->extensionConfiguration->get('canto_saas_fal', 'metadata_hook_token');
        $success = false;
        if ($metadataToken === $json['secure_token']) {
            $event = MetadataWebhookEvent::fromJsonArray($json);
            $this->eventDispatcher->dispatch($event);
            $success = true;
        }

        return json_encode(['success' => $success]);
    }
}
