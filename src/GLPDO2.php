<?php

namespace GeekLab\GLPDO2;

Class GLPDO2
{
    private $PDO;

    public function __construct(\PDO $pdo)
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
     * @return int
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
     * @return int
     */
    public function queryDelete(Statement $SQL): int
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform UPDATE query
     * @param Statement $SQL
     *
     * @return int
     */
    public function queryUpdate(Statement $SQL): int
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform INSERT query
     * @param Statement $SQL
     *
     * @return bool|string
     */
    public function queryInsert(Statement $SQL)
    {
        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        if (!$sth->rowCount())
        {
            // Insert failed
            return FALSE;
        }

        // look up the last inserted id
        $insert_id = $this->PDO->lastInsertId();

        // Return the ID
        return $insert_id;
    }

    /**
     * Return multiple rows result as an array
     *
     * @param Statement $SQL
     * @param string $kKey
     * @param string $vKey
     * @return array
     */
    public function selectRows(Statement $SQL, string $kKey = "", string $vKey = ""): array
    {
        $data = array();

        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        // Reform the results
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC))
        {
            // No kKey or vKey. Save enumerated row
            if (!$kKey)
            {
                $data[] = $row;
            }
            else
            {
                // kKey exists, save without vKey or with?
                $data[$row[$kKey]] = (!$vKey) ? $row : $row[$vKey];
            }
        }

        return $data;
    }

    /**
     * Execute statement and returns first row of results as an associative array.
     *
     * @param Statement $SQL
     * @return mixed
     */
    public function selectRow(Statement $SQL)
    {
        // Execute the statement
        $sth = $SQL->execute($this->PDO);

        // Return the first row fetched
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Executes statement and return a specific column from the first row of results.
     *
     * @param Statement $SQL
     * @param           $column
     * @param  bool     $caseSensitive
     * @param  bool     $default
     * @return string
     */
    public function selectValue(Statement $SQL, $column, bool $caseSensitive = FALSE, bool $default = FALSE): string
    {
        $row = $this->selectRow($SQL);

        if (!$caseSensitive)
        {
            $row    = array_change_key_case($row);
            $column = strtolower($column);
        }

        return isset($row[$column]) ? $row[$column] : $default;
    }
}