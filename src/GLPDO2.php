<?php

namespace GeekLab\GLPDO2;

// Make EA inspection stop complaining.
use PDO;
use Exception;

class GLPDO2
{
    /** @var PDO $PDO */
    private $PDO;

    public function __construct(PDO $pdo)
    {
        $this->PDO = $pdo;
    }

    /**
     * Begin transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->PDO->beginTransaction();
    }

    /**
     * Is the operation in the middle of the transaction?
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->PDO->inTransaction();
    }

    /**
     * Commit transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->PDO->commit();
    }

    /**
     * Rollback transaction.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->PDO->rollBack();
    }

    /**
     * Perform UPDATE or DELETE query and return the number of affected rows.
     *
     * @param Statement $SQL
     *
     * @return int
     * @throws Exception
     */
    private function queryAffectedRows(Statement $SQL): int
    {
        // Execute statement
        $sth = $SQL->execute($this->PDO);

        // Return number of rows affected
        return $sth->rowCount();
    }

    /**
     * Perform DELETE query.
     *
     * @param Statement $SQL
     *
     * @return int
     * @throws Exception
     */
    public function queryDelete(Statement $SQL): int
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform UPDATE query
     *
     * @param Statement $SQL
     *
     * @return int
     * @throws Exception
     */
    public function queryUpdate(Statement $SQL): int
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform INSERT query
     *
     * @param Statement $SQL
     *
     * @return bool|string
     * @throws Exception
     */
    public function queryInsert(Statement $SQL)
    {
        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        if (!$sth->rowCount()) {
            // Insert failed
            return false;
        }

        // Return the ID
        return $this->PDO->lastInsertId();
    }

    /**
     * Return multiple rows result as an array
     *
     * @param Statement $SQL
     *
     * @return array|false
     * @throws Exception
     */
    public function selectRows(Statement $SQL)
    {
        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute statement and returns first row of results as an associative array.
     *
     * @param Statement $SQL
     *
     * @return mixed
     * @throws Exception
     */
    public function selectRow(Statement $SQL)
    {
        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        // Return the first row fetched
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Executes statement and return a specific column from the first row of results.
     *
     * @param Statement $SQL
     * @param string    $column
     * @param bool      $caseSensitive
     * @param mixed     $default
     *
     * @return string|null
     * @throws Exception
     */
    public function selectValue(
        Statement $SQL,
        string $column,
        bool $caseSensitive = false,
        $default = null
    ): ?string {
        $row = $this->selectRow($SQL);

        if (!$caseSensitive) {
            $row = array_change_key_case($row);
            $column = strtolower($column);
        }

        return $row[$column] ?? $default;
    }
}
