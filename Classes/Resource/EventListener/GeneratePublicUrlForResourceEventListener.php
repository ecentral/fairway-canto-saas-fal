<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Utility\CantoUtility;
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
            if (CantoUtility::isMdcActivated($event->getStorage()->getConfiguration())) {
                // This applies a public url for the given asset.
                // If the file has been registered as a mdc-asset, then this returns the url for it
                // Otherwise we get the url to the downloaded resource instead
                $url = $event->getDriver()->getPublicUrl($identifier);
                $event->setPublicUrl($url);
            }
        } catch (\InvalidArgumentException $e) {
            // todo: we should add logging in the future
        }
    }
}
