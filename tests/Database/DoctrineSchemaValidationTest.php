<?php

namespace App\Tests\Database;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineSchemaValidationTest extends KernelTestCase
{
    public function testDoctrineSchemaIsValid(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->getConfiguration()->setSchemaAssetsFilter(static function ($asset) {
            $name = is_string($asset) ? $asset : $asset->getName();
            return $name !== 'doctrine_migration_versions';
        });
        $validator = new SchemaValidator($em);

        $mappingErrors = $validator->validateMapping();
        $this->assertEmpty($mappingErrors, sprintf(
            'Doctrine mapping errors found: %s',
            json_encode($mappingErrors, JSON_PRETTY_PRINT)
        ));

        $schemaInSync = $validator->schemaInSyncWithMetadata();
        $this->assertTrue($schemaInSync, 'The database schema is not in sync with the Doctrine mapping files.');
    }
}
