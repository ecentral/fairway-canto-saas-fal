<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\EventListener;

use Fairway\CantoSaasFal\Resource\Metadata\Exporter;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The Filelist updates the metadata through the data handler, thus is not triggering any Metadata Repository Events.
 * After the new data has been saved, this event is dispatched, pre-filling the form in the EditDocument-Controller of the Filelist
 * Through the Request and its data it can then be determined what action has been performed and then acted upon.
 * Due to a not deeply investigated situation, the event is automatically being stopped propagating after the SignalSlot-Replacement-Listener has been executed.
 * Additionally, it was not possible to hook right before the Signal Slot (v10.4) which is why this EventListener both listens to the Signal @see ext_locatconf and the event.
 */
final class AfterFormEnginePageInitializedEventListener
{
    public function __invoke(AfterFormEnginePageInitializedEvent $event)
    {
        $this->updateMetadataInCantoSlot($event->getController(), $event->getRequest());
    }

    public function updateMetadataInCantoSlot(EditDocumentController $controller, ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'POST' && $request->getQueryParams()['route'] === '/record/edit') {
            $data = $this->accessProtectedDataProperty($controller);
            if (isset($data['sys_file_metadata'])) {
                $exporter = GeneralUtility::getContainer()->get(Exporter::class);
                assert($exporter instanceof Exporter);
                foreach ($data['sys_file_metadata'] as $uid => $metadata) {
                    $exporter->exportToCanto($uid, $metadata);
                }
            }
        }
    }

    public function accessProtectedDataProperty(EditDocumentController $controller)
    {
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        return $property->getValue($controller);
    }
}
