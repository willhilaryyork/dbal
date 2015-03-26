<?php

/** @testCase */

namespace NextrasTests\Dbal;

use Mockery;
use Nextras\Dbal\Result\Result;
use Nextras\Dbal\Result\Row;
use Nextras\Dbal\Utils\DateTime;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


class ResultTest extends TestCase
{

	public function testIterator()
	{
		$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
		$adapter->shouldReceive('getTypes')->once()->andReturn([]);
		$adapter->shouldReceive('seek')->once()->with(0);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First']);
		$adapter->shouldReceive('fetch')->once()->andReturn(NULL);
		$adapter->shouldReceive('seek')->once()->with(0);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First']);

		$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');

		$names = [];
		$result = new Result($adapter, $driver, 0);
		$result->setValueNormalization(FALSE);
		foreach ($result as $row) {
			$names[] = $row->name;
		}

		Assert::same(['First'], $names);

		Assert::false($result->valid());
		Assert::null($result->current());
		Assert::same(1, $result->key());

		$result->rewind();
		Assert::truthy($result->current());
		Assert::same('First', $result->current()->name);
		Assert::true($result->valid());
		Assert::same(0, $result->key());
	}


	public function testSeek()
	{
		$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
		$adapter->shouldReceive('getTypes')->once()->andReturn([]);
		$adapter->shouldReceive('seek')->once();
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First', 'surname' => 'Two']);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'Third', 'surname' => 'Four']);
		$adapter->shouldReceive('fetch')->once()->andReturn(NULL);

		$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');

		$result = new Result($adapter, $driver, 0);
		$result->setValueNormalization(FALSE);

		$names = [];
		$result->seek(0);
		while ($row = $result->fetch()) {
			$names[] = $row->name;
		}

		Assert::same(['First', 'Third'], $names);
	}


	public function testFetchField()
	{
		$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
		$adapter->shouldReceive('getTypes')->once()->andReturn([]);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First', 'surname' => 'Two']);

		$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');

		$result = new Result($adapter, $driver, 0);
		$result->setValueNormalization(FALSE);
		Assert::same('First', $result->fetchField());


		$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
		$adapter->shouldReceive('getTypes')->once()->andReturn([]);
		$adapter->shouldReceive('fetch')->once()->andReturn(NULL);

		$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');

		$result = new Result($adapter, $driver, 0);
		$result->setValueNormalization(FALSE);
		Assert::null($result->fetchField());
	}


	public function testFetchAll()
	{
		$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
		$adapter->shouldReceive('getTypes')->once()->andReturn([]);
		$adapter->shouldReceive('seek')->once();
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First', 'surname' => 'Two']);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'Third', 'surname' => 'Four']);
		$adapter->shouldReceive('fetch')->once()->andReturn(NULL);
		$adapter->shouldReceive('seek')->once();
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'First', 'surname' => 'Two']);
		$adapter->shouldReceive('fetch')->once()->andReturn(['name' => 'Third', 'surname' => 'Four']);
		$adapter->shouldReceive('fetch')->once()->andReturn(NULL);

		$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');

		$result = new Result($adapter, $driver, 0);
		$result->setValueNormalization(FALSE);

		Assert::equal([
			new Row(['name' => 'First', 'surname' => 'Two']),
			new Row(['name' => 'Third', 'surname' => 'Four']),
		], $result->fetchAll());

		Assert::equal([
			new Row(['name' => 'First', 'surname' => 'Two']),
			new Row(['name' => 'Third', 'surname' => 'Four']),
		], $result->fetchAll());
	}


	public function testFetchPairs()
	{
		$one = [
			'name' => 'jon snow',
			'born' => new DateTime('2014-01-01'),
			'n' => 10
		];
		$two = [
			'name' => 'oberyn martell',
			'born' => new DateTime('2014-01-03'),
			'n' => 12,
		];
		$createResult = function() use ($one, $two) {
			$adapter = Mockery::mock('Nextras\Dbal\Drivers\IResultAdapter');
			$adapter->shouldReceive('getTypes')->once()->andReturn([]);
			$adapter->shouldReceive('seek')->once();
			$adapter->shouldReceive('fetch')->once()->andReturn($one);
			$adapter->shouldReceive('fetch')->once()->andReturn($two);
			$adapter->shouldReceive('fetch')->once()->andReturn(NULL);

			$driver = Mockery::mock('Nextras\Dbal\Drivers\IDriver');
			$result = new Result($adapter, $driver, 0);
			$result->setValueNormalization(FALSE);
			return $result;
		};

		Assert::same([
			10,
			12,
		], $createResult()->fetchPairs(NULL, 'n'));

		Assert::equal([
			10 => new Row($one),
			12 => new Row($two),
		], $createResult()->fetchPairs('n', NULL));

		Assert::same([
			10 => 'jon snow',
			12 => 'oberyn martell',
		], $createResult()->fetchPairs('n', 'name'));

		Assert::equal([
			'2014-01-01T00:00:00+01:00' => new Row($one),
			'2014-01-03T00:00:00+01:00' => new Row($two),
		], $createResult()->fetchPairs('born', NULL));

		Assert::same([
			'2014-01-01T00:00:00+01:00' => 'jon snow',
			'2014-01-03T00:00:00+01:00' => 'oberyn martell',
		], $createResult()->fetchPairs('born', 'name'));
	}

}


$test = new ResultTest();
$test->run();
