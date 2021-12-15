<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\Resource\Event;

final class MdcEnabledCheckEvent
{
    private bool $isMdcEnabled;

    public function __construct(bool $isMdcEnabled)
    {
        $this->isMdcEnabled = $isMdcEnabled;
    }

    public function setIsMdcEnabled(bool $isMdcEnabled): void
    {
        $this->isMdcEnabled = $isMdcEnabled;
    }

    public function isMdcEnabled(): bool
    {
        return $this->isMdcEnabled;
    }
}
