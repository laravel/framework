<?php

namespace Illuminate\Database;

/**
 * Database connection transaction with guarantee of proper commit/roll back level.
 */
class Transaction
{
	/**
	 * Connection this transaction is created on.
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 * Transaction level of the connection after transaction was began.
	 *
	 * @var int
	 */
	private $level;

	/**
	 * Whether transaction has already begun.
	 *
	 * @var bool
	 */
	private $hasBegun = false;

	/**
	 * Whether transaction is closed (committed or rolled back).
	 *
	 * @var bool
	 */
	private $isClosed = false;

	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Begin the transaction.
	 *
	 * May not be began twice.
	 *
	 * @return void
	 */
	public function begin(): void
	{
		assert(!$this->hasBegun, 'Transaction may not be began twice.');

		$this->connection->beginTransaction();

		$this->level = $this->connection->transactionLevel();
		$this->hasBegun = true;
	}

	/**
	 * Commit the transaction.
	 *
	 * May not be committed twice or after rollback.
	 *
	 * @return void
	 */
	public function commit(): void
	{
		$this->assertNotClosed();
		assert($this->connection->transactionLevel() >= $this->level, 'Transaction has already been committed or rolled back.');

		$this->connection->commit();

		$this->close();
	}

	/**
	 * Rollback the transaction.
	 *
	 * May not be rolled back twice or after commit.
	 *
	 * @return void
	 */
	public function rollBack(): void
	{
		$this->assertNotClosed();

		// Rollback to level that was before starting a transaction.
		// If current level is lower or equal to what we want - we don't care, it's already rolled back.
		// If current level is higher - Laravel will rollback to the level we need.
		$this->connection->rollBack($this->level - 1);

		$this->close();
	}

	/**
	 * Level of this transaction.
	 */
	public function level(): int
	{
		return $this->level;
	}

	/**
	 * Assert it hasn't been closed yet.
	 *
	 * @return void
	 */
	private function assertNotClosed(): void
	{
		assert(!$this->isClosed, 'Transaction is already closed - either committed or rolled back.');
	}

	/**
	 * Close the transaction, disallowing any following commits or rollbacks.
	 *
	 * @return void
	 */
	private function close(): void
	{
		$this->isClosed = true;
	}
}
