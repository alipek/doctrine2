<?php
namespace Doctrine\ORM\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\BatchEntityPersister;

class Configuration
{
    /**
     * Default persister class name
     * @var string
     */
    protected $defaultEntityPersisterClass = BasicEntityPersister::class;

    /**
     * Current persister class name
     * @var string
     */
    protected $entityPersisterClass;

    /**
     * Is events system enabled
     * @var bool
     */
    protected $isEventsSystemEnabled = true;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /**
     * Max count of entities per one insert
     *
     * @var int
     */
    protected $maxPerInsert = 100;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityPersisterClass = $this->defaultEntityPersisterClass;
        $this->entityManager = $entityManagerInterface;
    }

    public function enableBatchInsert()
    {
        $this->entityPersisterClass = BatchEntityPersister::class;
    }

    /**
     * @return boolean
     */
    public function isIsEventsSystemEnabled()
    {
        return $this->isEventsSystemEnabled;
    }

    /**
     * @param boolean $isEventsSystemEnabled
     */
    public function setIsEventsSystemEnabled($isEventsSystemEnabled)
    {
        $this->isEventsSystemEnabled = $isEventsSystemEnabled;
    }

    /**
     * @return string
     */
    public function getEntityPersisterClass()
    {
        return $this->entityPersisterClass;
    }

    /**
     * @param string $entityPersisterClass
     */
    public function setEntityPersisterClass($entityPersisterClass)
    {
        $this->entityPersisterClass = $entityPersisterClass;
    }

    /**
     * @param ClassMetadata $metadata
     * @return \Doctrine\ORM\Persisters\Entity\EntityPersister
     */
    public function newEntityPersister(ClassMetadata $metadata)
    {
        $obj = new $this->entityPersisterClass($this->enityManager, $metadata);

        if($obj instanceof HasPerfomanceConfiguration)
            $obj->setPerformanceConfiguration($this);

        return;
    }

    /**
     * @return string
     */
    public function getDefaultEntityPersisterClass()
    {
        return $this->defaultEntityPersisterClass;
    }

    /**
     * @param string $defaultEntityPersisterClass
     */
    public function setDefaultEntityPersisterClass($defaultEntityPersisterClass)
    {
        $this->defaultEntityPersisterClass = $defaultEntityPersisterClass;
    }

    /**
     * @return int
     */
    public function getMaxPerInsert()
    {
        return $this->maxPerInsert;
    }

    /**
     * @param int $maxPerInsert
     */
    public function setMaxPerInsert($maxPerInsert)
    {
        $this->maxPerInsert = (int)$maxPerInsert;
    }
}