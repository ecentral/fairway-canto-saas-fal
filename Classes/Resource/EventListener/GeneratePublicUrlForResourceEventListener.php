<?php

declare(strict_types=1);

namespace Ecentral\CantoSaasFal\Resource\EventListener;

use Ecentral\CantoSaasFal\Utility\CantoUtility;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;

final class GeneratePublicUrlForResourceEventListener
{
    public function __invoke(GeneratePublicUrlForResourceEvent $event): void
    {
        $file = $event->getResource();
        $identifier = null;
        if ($file instanceof File) {
            $identifier = $file->getIdentifier();
        }
        if ($file instanceof ProcessedFile) {
            $identifier = $file->getOriginalFile()->getIdentifier();
        }
        if (!$identifier) {
            return;
        }
        try {
            if (CantoUtility::useMdcCDN($identifier)) {
                // This applies a public url for the given asset.
                // If the file has been registered as a mdc-asset, then this returns the url for it
                // Otherwise we get the url to the downloaded resource instead
                $url = $event->getDriver()->getPublicUrl($identifier);
                $event->setPublicUrl($url);
            }
        } catch (\InvalidArgumentException $e) {
        }
    }
}
