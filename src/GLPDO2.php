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
     * Begin A Transaction ----
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->PDO->beginTransaction();
    }

    /**
     * Commit transaction ----
     *
     * @return bool
     */
    public function commit()
    {
        return $this->PDO->commit();
    }

    /**
     * Rollback transaction ----
     * @return bool
     */
    public function rollback()
    {
        return $this->PDO->rollBack();
    }

    /**
     * Perform 'UPDATE' or 'DELETE' query and return the number of affected rows
     *
     * @param Statement $SQL
     *
     * @return int|null
     */
    private function queryAffectedRows(Statement $SQL)
    {
        // Execute statement
        $sth = $SQL->execute($this->PDO);

        // Return number of rows affected
        return $sth->rowCount();
    }

    /**
     * Perform 'DELETE' query
     *
     * @static
     * @param Statement $SQL
     *
     * @return mixed
     */
    public function queryDelete(Statement $SQL)
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform 'UPDATE' query
     *
     * @static
     * @param Statement $SQL
     *
     * @return mixed
     */
    public function queryUpdate(Statement $SQL)
    {
        return $this->queryAffectedRows($SQL);
    }

    /**
     * Perform 'INSERT' query
     *
     * @static
     * @param Statement $SQL
     *
     * @return mixed
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
     * @static
     * @param Statement $SQL
     * @param string $kKey
     * @param string $vKey
     *
     * @return array|string
     */
    public function selectRows(Statement $SQL, $kKey = "", $vKey = "")
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
     *
     * @return mixed|null
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
     * @return bool
     */
    public function selectValue(Statement $SQL, $column, $caseSensitive = FALSE, $default = FALSE)
    {
        $row = $this->selectRow($SQL);

        if(!$caseSensitive)
        {
            $row = array_change_key_case($row);
            $column = strtolower($column);
        }

        return isset($row[$column]) ? $row[$column] : $default;
    }
}