<?php

/**
 * @testCase
 * @dataProvider? ../../databases.ini
 */

namespace NextrasTests\Dbal;

use Nextras\Dbal\InvalidStateException;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Tester\Assert;


require_once __DIR__ . '/../../bootstrap.php';


class ResultIntegrationTest extends IntegrationTestCase
{

	public function testEmptyResult()
	{
		$result = $this->connection->query('SELECT * FROM books WHERE 1=2');
		Assert::equal([], iterator_to_array($result));
	}


	public function testSetupNormalization()
	{
		$this->initData($this->connection);

		$result = $this->connection->query('SELECT * FROM tag_followers ORDER BY tag_id, author_id');

		$result->setValueNormalization(FALSE); // test reenabling
		$result->setValueNormalization(TRUE);

		$follower = $result->fetch();

		Assert::same(1, $follower->tag_id);
		Assert::same(1, $follower->author_id);
		Assert::type(DateTimeImmutable::class, $follower->created_at);
		Assert::same('2014-01-01 00:10:00', $follower->created_at->format('Y-m-d H:i:s'));


		$result->setValueNormalization(FALSE);
		$follower = $result->fetch();

		if (defined('HHVM_VERSION')) {
			Assert::same(2, $follower->tag_id);
			Assert::same(2, $follower->author_id);
		} else {
			Assert::same('2', $follower->tag_id);
			Assert::same('2', $follower->author_id);
		}
		Assert::type('string', $follower->created_at);
	}


	public function testSeek()
	{
		$this->initData($this->connection);
		$result = $this->connection->query('SELECT * FROM books');

		Assert::exception(function() use ($result) {
			$result->seek(10);
		}, InvalidStateException::class);
	}

}


$test = new ResultIntegrationTest();
$test->run();
