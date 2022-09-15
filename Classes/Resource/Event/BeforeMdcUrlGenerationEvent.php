<?php

declare(strict_types=1);

/*
 * This file is part of the "canto_saas_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\CantoSaasFal\Resource\Event;

/**
 * The canto MDC differentiates between Scaling First and Cropping First.
 * This extension uses the opinionated first scaling then cropping way.
 * If it seems to be necessary to override this method, then interact with this method and change the order.
 * As this changes the way the images are being used, it might become necessary to adapt the scaling and cropping parameters accordingly.
 * Through this event it should be possible to interact with those parameters and get the desired result.
 */
final class BeforeMdcUrlGenerationEvent
{
    private string $croppingString;
    private string $formattingString;
    private string $scalingString;
    private bool $firstScaleThenCrop;
    private array $configuration;

    public function __construct(array $configuration, string $scalingString, string $croppingString, string $formattingString, bool $firstScaleThenCrop)
    {
        $this->configuration = $configuration;
        $this->croppingString = $croppingString;
        $this->formattingString = $formattingString;
        $this->scalingString = $scalingString;
        $this->firstScaleThenCrop = $firstScaleThenCrop;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setCroppingString(string $croppingString): self
    {
        $this->croppingString = $croppingString;
        return $this;
    }

    public function setFormattingString(string $formattingString): self
    {
        $this->formattingString = $formattingString;
        return $this;
    }

    public function setScalingString(string $scalingString): self
    {
        $this->scalingString = $scalingString;
        return $this;
    }

    public function setFirstScaleThenCrop(bool $firstScaleThenCrop): self
    {
        $this->firstScaleThenCrop = $firstScaleThenCrop;
        return $this;
    }

    public function getMdcUrl(): string
    {
        if ($this->firstScaleThenCrop) {
            return sprintf('%s%s%s', $this->scalingString, $this->croppingString, $this->formattingString);
        }
        return sprintf('%s%s%s', $this->croppingString, $this->scalingString, $this->formattingString);
    }
}
