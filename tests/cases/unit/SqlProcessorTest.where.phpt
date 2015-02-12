<?php

/** @testCase */

namespace NextrasTests\Dbal;

use Mockery;
use Nextras\Dbal\Drivers\IDriver;
use Nextras\Dbal\SqlProcessor;
use stdClass;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


class SqlProcessorWhereTest extends TestCase
{
	/** @var IDriver|Mockery\MockInterface */
	private $driver;

	/** @var SqlProcessor */
	private $parser;


	protected function setUp()
	{
		parent::setUp();
		$this->driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');
		$this->parser = new SqlProcessor($this->driver);
	}


	public function testAssoc()
	{
		$this->driver->shouldReceive('convertToSql')->once()->with('a', IDriver::TYPE_IDENTIFIER)->andReturn('A');
		$this->driver->shouldReceive('convertToSql')->once()->with('b.c', IDriver::TYPE_IDENTIFIER)->andReturn('BC');
		$this->driver->shouldReceive('convertToSql')->once()->with('d', IDriver::TYPE_IDENTIFIER)->andReturn('D');
		$this->driver->shouldReceive('convertToSql')->once()->with('e', IDriver::TYPE_IDENTIFIER)->andReturn('E');
		$this->driver->shouldReceive('convertToSql')->once()->with('f', IDriver::TYPE_IDENTIFIER)->andReturn('F');

		$this->driver->shouldReceive('convertToSql')->once()->with(1, IDriver::TYPE_STRING)->andReturn("'1'");
		$this->driver->shouldReceive('convertToSql')->twice()->with('a', IDriver::TYPE_STRING)->andReturn("'a'");

		Assert::same(
			'A = 1 AND BC = 2 AND D IS NULL AND E IN (\'1\', \'a\') AND F IN (1, \'a\')',
			$this->parser->processModifier('and', [
				'a%i' => '1',
				'b.c' => 2,
				'd%s?' => NULL,
				'e%s[]' => ['1', 'a'],
				'f%any[]' => [1, 'a'],
			])
		);
	}


	public function testComplex()
	{
		$this->driver->shouldReceive('convertToSql')->once()->with('a', IDriver::TYPE_IDENTIFIER)->andReturn('a');
		$this->driver->shouldReceive('convertToSql')->once()->with('b', IDriver::TYPE_IDENTIFIER)->andReturn('b');

		Assert::same(
			'(a = 1 AND b IS NULL) OR a = 2 OR (a IS NULL AND b = 1) OR b = 3',
			$this->parser->processModifier('or', [
				['%and', ['a%i?' => 1, 'b%i?' => NULL]],
				'a' => 2,
				['%and', ['a%i?' => NULL, 'b%i?' => 1]],
				'b' => 3,
			])
		);
	}


	public function testEmptyConds()
	{
		Assert::same(
			'1=1',
			$this->parser->processModifier('and', [])
		);

		Assert::same(
			'1=1',
			$this->parser->processModifier('or', [])
		);
	}


	/**
	 * @dataProvider provideInvalidData
	 */
	public function testInvalid($type, $value, $message)
	{
		$this->driver->shouldIgnoreMissing('X');
		Assert::throws(
			function() use ($type, $value) {
				$this->parser->processModifier($type, $value);
			},
			'Nextras\Dbal\Exceptions\InvalidArgumentException', $message
		);
	}


	public function provideInvalidData()
	{
		return [
			['and', 123, 'Modifier %and expects value to be array, integer given.'],
			['and', NULL, 'Modifier %and expects value to be array, NULL given.'],

			['and', ['s'], 'Modifier %and requires items with numeric index to be array, string given.'],
			['and', ['a%i' => 's'], 'Modifier %i expects value to be int, string given.'],
			['and', ['a%i[]' => 123], 'Modifier %i[] expects value to be array, integer given.'],
			['and', ['a' => new stdClass()], 'Modifier %any can handle pretty much anything but not stdClass.'],
			['and', ['a%foo' => 's'], 'Unknown modifier %foo.'],

			['and?', [], 'Modifier %and does not have %and? variant.'],
			['and[]', [], 'Modifier %and does not have %and[] variant.'],
			['and?[]', [], 'Modifier %and does not have %and?[] variant.'],

			['or', 123, 'Modifier %or expects value to be array, integer given.'],
			['or', NULL, 'Modifier %or expects value to be array, NULL given.'],

			['or', ['s'], 'Modifier %or requires items with numeric index to be array, string given.'],
			['or', ['a%i' => 's'], 'Modifier %i expects value to be int, string given.'],
			['or', ['a%i[]' => 123], 'Modifier %i[] expects value to be array, integer given.'],
			['or', ['a' => new stdClass()], 'Modifier %any can handle pretty much anything but not stdClass.'],
			['or', ['a%foo' => 's'], 'Unknown modifier %foo.'],

			['or?', [], 'Modifier %or does not have %or? variant.'],
			['or[]', [], 'Modifier %or does not have %or[] variant.'],
			['or?[]', [], 'Modifier %or does not have %or?[] variant.'],
		];
	}

}

$test = new SqlProcessorWhereTest();
$test->run();
