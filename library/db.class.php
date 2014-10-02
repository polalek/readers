<?php

/**
 * Created by PhpStorm.
 * User: alexeyp
 * Date: 31.07.14
 * Time: 2:27
 */
Class db extends mysqli
{
    private $_conn = null;

    public function __construct()
    {
        parent::__construct('localhost', 'root', 'apolunin', 'ar', '3306');
        $this->query("SET NAMES 'utf8'");
    }

    public function select($sql, $type, $opt = '', $unset = '')
    {
        echo $sql . PHP_EOL;
        $dbres = null;
        $result = $this->query($sql);
        if ($result) {
            switch ($type) {
                case 'arow':
                    $dbres = $result->fetch_assoc();
                    break;

                case 'row':
                    $dbres = $result->fetch_array();
                    break;

                case 'arows':
                    $dbres = array();
                    while (($row = $result->fetch_assoc())) {
                        $dbres[] = $row;
                    }
                    break;

                case 'rows':
                    $dbres = array();
                    while (($row = $result->fetch_array())) {
                        $dbres[] = $row;
                    }
                    break;

                case 'array':
                    $dbres = array();
                    while (($row = $result->fetch_array())) {
                        $dbres[] = $row[0];
                    }
                    break;

                case '1=>2':
                    $dbres = array();
                    while (($row = $result->fetch_array())) {
                        $dbres[$row[0]] = $row[1];
                    }
                    break;

                case '1=>2u':
                    $dbres = array();
                    while (($row = $result->fetch_array())) {
                        if (!isset($dbres[$row[0]]))
                            $dbres[$row[0]] = $row[1];
                        elseif (!$dbres[$row[0]]) {
                            unset($dbres[$row[0]]);
                            $dbres[$row[0]] = $row[1];
                        }
                    }
                    break;

                case '1=>array':
                    $dbres = array();
                    if ($opt) {
                        if ($unset) {
                            while (($row = $result->fetch_assoc())) {
                                $k = $row[$opt];
                                unset($row[$opt]);
                                $dbres[$k][] = $row;
                            }
                        } else {
                            while (($row = $result->fetch_assoc())) {
                                $dbres[$row[$opt]][] = $row;
                            }
                        }
                    }
                    break;


                case 'carows':
                    $dbres = array();
                    if ($opt) {
                        while (($row = $result->fetch_assoc())) {
                            if (isset($row[$opt])) {
                                $dbres[$row[$opt]] = $row;
                            }
                        }
                    }
                    break;

                case 'value':
                case 'count':
                    $row = $result->fetch_array();
                    if (is_array($row)) {
                        $dbres = $row[0];
                    }
                    break;
                case 'values':
                    $dbres = array();
                    while (($row = $result->fetch_array()))
                        if (is_array($row)) {
                            $dbres[] = $row[0];
                        }
                    break;
                case 'no':
                    $dbres = $this->errno > 0;
                    break;

            }
        }
        if ($this->errno > 0) {
            echo $sql . PHP_EOL;
            die();
        }
        return $dbres;
    }

    function exec($sql, $type = '')
    {
        echo $sql . PHP_EOL;
        $res = $this->query($sql);

        if ($this->errno > 0) {
            echo $sql . PHP_EOL;
            die();
        }
        return ($type == 'inserted_id' ? $this->insert_id : $res);
    }

}