<?php

namespace Doctrine\ORM\Persisters\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Performance\Configuration;
use Doctrine\ORM\Performance\HasPerfomanceConfiguration;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\SqlExpressionVisitor;
use Doctrine\ORM\Persisters\SqlValueVisitor;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Doctrine\ORM\Utility\PersisterHelper;

/**
 * Implements batch insert logic
 *
 * Class BatchEntityPersister
 * @package Doctrine\ORM\Persisters\Entity
 */
class BatchEntityPersister extends BasicEntityPersister implements HasPerfomanceConfiguration
{
    /**
     * @var \Doctrine\ORM\Performance\Configuration
     */
    protected $performanceConfiguration;

    /**
     * @var
     */
    protected $insertValueSql;

    /**
     * Initializes a new <tt>BasicEntityPersister</tt> that uses the given EntityManager
     * and persists instances of the class described by the given ClassMetadata descriptor.
     *
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $class
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function getPerformanceConfiguration()
    {
        return $this->performanceConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function setPerformanceConfiguration(Configuration $configuration)
    {
        $this->performanceConfiguration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function executeInserts()
    {
        if (!$this->queuedInserts) {
            return array();
        }

        $postInsertIds  = array();
        $idGenerator    = $this->class->idGenerator;
        $isPostInsertId = $idGenerator->isPostInsertGenerator();
        $maxPerInsert = $this->performanceConfiguration->getMaxPerInsert();
        $queryCount = count($this->queuedInserts);
        $chunks = ceil($queryCount / $maxPerInsert);
        $queuedInserts = array_values($this->queuedInserts);
        $tableName  = $this->class->getTableName();

        for($i=0; $i < $chunks; $i++)
        {
            $sql = '';
            $indexOffset = $i * $maxPerInsert;
            if($indexOffset + $maxPerInsert < $queryCount)
                $sql = $this->getMultiInsertSQL($maxPerInsert);
            else
                $sql = $this->getMultiInsertSQL($queryCount - $indexOffset);

            $stmt = $this->conn->prepare($sql);

            $paramIndex = 1;
            $insertedCount = 0;

            for($j=$indexOffset; $j < $indexOffset+$maxPerInsert && $j < $queryCount; $j++)
            {
                $entity = $queuedInserts[$j];
                $insertData = $this->prepareInsertData($entity);

                if (isset($insertData[$tableName])) {

                    foreach ($insertData[$tableName] as $column => $value) {
                        $stmt->bindValue($paramIndex++, $value, $this->columnTypes[$column]);
                    }
                }
                $insertedCount++;
            }

            $stmt->execute();

            if ($isPostInsertId) {
                $generatedId = $idGenerator->generate($this->em, $entity);
                $id = array(
                    $this->class->identifier[0] => $generatedId
                );

                $idOffset = 0;
                do {
                    $postInsertIds[] = array(
                        'generatedId' => $generatedId,
                        'entity' => $queuedInserts[$indexOffset + $idOffset],
                    );
                    $generatedId++;
                    $idOffset++;
                } while($idOffset < $insertedCount);
            } else {
                $id = $this->class->getIdentifierValues($entity);
            }

            if ($this->class->isVersioned) {
                $this->assignDefaultVersionValue($entity, $id);
            }

            $stmt->closeCursor();
        }

        $this->queuedInserts = array();

        return $postInsertIds;
    }

    protected function getMultiInsertSQL($valuesCount = 1)
    {
        if ($this->insertSql !== null) {
            return $this->insertSql. implode(", ",  array_fill(0, $valuesCount, $this->insertValueSql));;
        }

        $columns   = $this->getInsertColumnList();
        $tableName = $this->quoteStrategy->getTableName($this->class, $this->platform);

        if (empty($columns)) {
            $identityColumn  = $this->quoteStrategy->getColumnName($this->class->identifier[0], $this->class, $this->platform);
            $this->insertSql = $this->platform->getEmptyIdentityInsertSQL($tableName, $identityColumn);

            return $this->insertSql;
        }

        $values  = array();
        $columns = array_unique($columns);

        foreach ($columns as $column) {
            $placeholder = '?';

            if (isset($this->class->fieldNames[$column])
                && isset($this->columnTypes[$this->class->fieldNames[$column]])
                && isset($this->class->fieldMappings[$this->class->fieldNames[$column]]['requireSQLConversion'])) {
                $type        = Type::getType($this->columnTypes[$this->class->fieldNames[$column]]);
                $placeholder = $type->convertToDatabaseValueSQL('?', $this->platform);
            }

            $values[] = $placeholder;
        }

        $columns = implode(', ', $columns);
        $values  = implode(', ', $values);
        $this->insertValueSql = "($values)";

        $this->insertSql = sprintf('INSERT INTO %s (%s) VALUES ', $tableName, $columns);

        return $this->insertSql. implode(", ",  array_fill(0, $valuesCount, $this->insertValueSql));
    }
}
