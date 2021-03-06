<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 5:15 PM
 */

namespace VertigoLabs\TsVector;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Base\BaseORMTestCase;
use TsVector\Fixture\FullAnnotationsEntity;
use VertigoLabs\DoctrineFullTextPostgres\Common\TsVectorSubscriber;
use TsVector\Fixture\Article;
use TsVector\Fixture\DefaultAnnotationsEntity;
use TsVector\Fixture\MissingColumnEntity;
use TsVector\Fixture\WrongColumnTypeEntity;

class TsVectorTest extends BaseORMTestCase
{
	public function setUp()
	{
		parent::setUp();

		$evm = new EventManager();
		$evm->addEventSubscriber(new TsVectorSubscriber());
//		$this->getMock
	}

	/**
	 */
	public function shouldReceiveAnnotation()
	{
		$reader = new AnnotationReader();
		$refObj = new \ReflectionClass(Article::class);

		$titleProp = $refObj->getProperty('title');
		$bodyProp = $refObj->getProperty('body');
		$titleAnnotation = $reader->getPropertyAnnotation($titleProp, 'Vertigolabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\TsVector');
		$bodyAnnotation = $reader->getPropertyAnnotation($bodyProp, 'Vertigolabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\TsVector');

		$this->assertNotNull($titleAnnotation,'TsVector annotation not found for title');
		$this->assertNotNull($bodyAnnotation,'TsVector annotation not found for body');
	}

	/**
	 * @test
	 */
	public function shouldReceiveDefaults()
	{
		$metaData = $this->em->getClassMetadata(DefaultAnnotationsEntity::class);

		$allDefaultsMetadata = $metaData->getFieldMapping('allDefaultsFTS');

		$this->assertEquals('allDefaultsFTS', $allDefaultsMetadata['fieldName']);
		$this->assertEquals('allDefaultsFTS', $allDefaultsMetadata['columnName']);
		$this->assertEquals('D', $allDefaultsMetadata['weight']);
		$this->assertEquals('english', $allDefaultsMetadata['language']);
	}

	/**
	 * @test
	 */
	public function shouldReceiveCustom()
	{
		$metaData = $this->em->getClassMetadata(FullAnnotationsEntity::class);

		$allDefaultsMetadata = $metaData->getFieldMapping('allCustomFTS');

		$this->assertEquals('allCustomFTS', $allDefaultsMetadata['fieldName']);
		$this->assertEquals('fts_custom', $allDefaultsMetadata['columnName']);
		$this->assertEquals('A', $allDefaultsMetadata['weight']);
		$this->assertEquals('french', $allDefaultsMetadata['language']);
	}

	/**
	 * @test
	 * @expectedException \Doctrine\ORM\Mapping\MappingException
	 * @expectedExceptionMessage Class does not contain missingColumn property
	 */
	public function mustHaveColumn()
	{
		$metaData = $this->em->getClassMetadata(MissingColumnEntity::class);
	}

	/**
	 * @test
	 * @expectedException \Doctrine\Common\Annotations\AnnotationException
	 * @expectedExceptionMessage TsVector\Fixture\WrongColumnTypeEntity::wrongColumnTypeFTS TsVector field can only be assigned to ( "string" | "text" | "array" | "simple_array" | "json" | "json_array" ) columns. TsVector\Fixture\WrongColumnTypeEntity::wrongColumnType has the type integer
	 */
	public function mustHaveCorrectColumnType()
	{
		$metaData = $this->em->getClassMetadata(WrongColumnTypeEntity::class);
	}

	/**
	 * @test
	 */
	public function shouldCreateSchema()
	{
		$classes = [
			$this->em->getClassMetadata(Article::class)
		];
		$sql = $this->schemaTool->getCreateSchemaSql($classes);

		$this->assertRegExp('/title_fts tsvector|body_fts tsvector/',$sql[0]);
	}

	/**
	 * @test
	 */
	public function shouldInsertData()
	{
		$this->setUpSchema([Article::class]);

		$article = new Article();
		$article->setTitle('test one');
		$article->setBody('This is test one');

		$this->em->persist($article);
		$this->em->flush();
	}

}