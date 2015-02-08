<?php

/** @testcase */

namespace NextrasTests\Dbal;

use Mockery\MockInterface;
use Nextras\Dbal\Drivers\IDriver;
use Nextras\Dbal\SqlProcessor;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


class SqlProcessorWhereTest extends TestCase
{
	/** @var MockInterface */
	private $driver;

	/** @var SqlProcessor */
	private $parser;


	protected function setUp()
	{
		parent::setUp();
		$this->driver = \Mockery::mock('Nextras\Dbal\Drivers\IDriver');
		$this->parser = new SqlProcessor($this->driver);
	}


	public function testWhereAnd()
	{
		$this->driver->shouldReceive('convertToSql')->once()->with('id', IDriver::TYPE_IDENTIFIER)->andReturn('id');
		$this->driver->shouldReceive('convertToSql')->once()->with('title', IDriver::TYPE_IDENTIFIER)->andReturn('title');
		$this->driver->shouldReceive('convertToSql')->once()->with('foo', IDriver::TYPE_IDENTIFIER)->andReturn('foo');

		$this->driver->shouldReceive('convertToSql')->once()->with("'foo'", IDriver::TYPE_STRING)->andReturn("'\\'foo\\''");
		$this->driver->shouldReceive('convertToSql')->once()->with(2, IDriver::TYPE_STRING)->andReturn("'2'");

		Assert::same(
			"SELECT 1 FROM foo WHERE id = 1 AND title = '\\'foo\\'' AND foo = '2'",
			$this->convert('SELECT 1 FROM foo WHERE %and', [
				'id%i' => 1,
				'title%s' => "'foo'",
				'foo' => 2,
			])
		);
	}


	public function testWhereOr()
	{
		$this->driver->shouldReceive('convertToSql')->once()->with('id', IDriver::TYPE_IDENTIFIER)->andReturn('id');
		$this->driver->shouldReceive('convertToSql')->once()->with('title', IDriver::TYPE_IDENTIFIER)->andReturn('title');
		$this->driver->shouldReceive('convertToSql')->once()->with('foo', IDriver::TYPE_IDENTIFIER)->andReturn('foo');

		$this->driver->shouldReceive('convertToSql')->once()->with("'foo'", IDriver::TYPE_STRING)->andReturn("'\\'foo\\''");
		$this->driver->shouldReceive('convertToSql')->once()->with(2, IDriver::TYPE_STRING)->andReturn("'2'");

		Assert::same(
			"SELECT 1 FROM foo WHERE id = 1 OR title = '\\'foo\\'' OR foo = '2'",
			$this->convert('SELECT 1 FROM foo WHERE %or', [
				'id%i' => 1,
				'title%s' => "'foo'",
				'foo' => 2,
			])
		);
	}


	private function convert($sql)
	{
		return $this->parser->process(func_get_args());
	}

}

$test = new SqlProcessorWhereTest();
$test->run();