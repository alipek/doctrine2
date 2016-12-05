<?php
namespace Doctrine\ORM\Performance;


interface HasPerfomanceConfiguration
{
    /**
     * Gets performance configuration
     *
     * @return \Doctrine\ORM\Performance\Configuration
     */
    public function getPerformanceConfiguration();

    /**
     * Sets performance configuration
     *
     * @param Configuration $configuration
     * @return void
     */
    public function setPerformanceConfiguration(Configuration $configuration);
}