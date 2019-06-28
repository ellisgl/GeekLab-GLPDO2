<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use \PDO;
use \Exception;

class GLPDO2
{
    /** @var PDO $pdo Should make private after final...? */
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Begin transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Is the operation in the middle of the transaction?
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Commit transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Perform UPDATE or DELETE query and return the number of affected rows.
     *
     * @param Statement $statement
     *
     * @return int
     * @throws Exception
     */
    private function queryAffectedRows(Statement $statement): int
    {
        // Execute statement
        $sth = $statement->execute($this->pdo);

        // Return number of rows affected
        return $sth->rowCount();
    }

    /**
     * Perform DELETE query.
     *
     * @param Statement $statement
     *
     * @return int
     * @throws Exception
     */
    public function queryDelete(Statement $statement): int
    {
        return $this->queryAffectedRows($statement);
    }

    /**
     * Perform UPDATE query
     *
     * @param Statement $statement
     *
     * @return int
     * @throws Exception
     */
    public function queryUpdate(Statement $statement): int
    {
        return $this->queryAffectedRows($statement);
    }

    /**
     * Perform INSERT query
     *
     * @param Statement $statement
     *
     * @return bool|string
     * @throws Exception
     */
    public function queryInsert(Statement $statement)
    {
        // Execute the statement
        $sth = $statement->execute($this->pdo);

        if (!$sth->rowCount()) {
            // Insert failed
            return false;
        }

        // Return the ID
        return $this->pdo->lastInsertId();
    }

    /**
     * Return multiple rows result as an array
     *
     * @param Statement $statement
     *
     * @return array|false
     * @throws Exception
     */
    public function selectRows(Statement $statement)
    {
        // Execute the statement
        $sth = $statement->execute($this->pdo);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute statement and returns first row of results as an associative array.
     *
     * @param Statement $statement
     *
     * @return mixed
     * @throws Exception
     */
    public function selectRow(Statement $statement)
    {
        // Execute the statement
        $sth = $statement->execute($this->pdo);

        // Return the first row fetched
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Executes statement and return a specific column from the first row of results.
     *
     * @param Statement $statement
     * @param string $column
     * @param mixed $default
     *
     * @return string|null
     * @throws Exception
     */
    public function selectValue(
        Statement $statement,
        string $column,
        $default = null
    ): ?string {
        $row = $this->selectRow($statement);
        $row = array_change_key_case($row);
        $column = strtolower($column);

        return $row[$column] ?? $default;
    }

    /**
     * Executes statement and return a specific column from the first row of results.
     *
     * @param Statement $statement
     * @param string $column
     * @param mixed $default
     *
     * @return string|null
     * @throws Exception
     */
    public function selectValueCaseSensitive(
        Statement $statement,
        string $column,
        $default = null
    ): ?string {
        $row = $this->selectRow($statement);

        return $row[$column] ?? $default;
    }
}
