<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Ecentral\CantoSaasFal\ViewHelpers\Format;

use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class FileNameViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $fileName = $renderChildrenClosure();
        if ($fileName === '') {
            return '';
        }
        $fileInfo = PathUtility::pathinfo($fileName);
        return sprintf(
            '%s.%s',
            str_replace(['-', '_'], ' ', ($fileInfo['filename'] ?? '')),
            ($fileInfo['extension'] ?? '')
        );
    }
}
