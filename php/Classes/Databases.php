<?php
declare(strict_types=1);
namespace Classes;

use PDOException;

class Database
{
    private $conn;

    /**
     * 直接與資料庫連線，若連線未建立，則連線至資料庫
     * ；若無，則新增一個連線。
     *
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        // 如果連線不存在
        if (!$this->conn) {
            try {
                $this->conn = new \PDO(
                    "mysql:host=db;dbname=web",
                    "web",
                    "web"
                );
            } catch (PDOException $e) {
                echo "Database error" . $e;
            }
        }
        return $this->conn;
    }

    /**
     * 直接執行指定的 sql 語法
     *
     * @param string $sql sql 語法
     * @param boolean $simple 使否用 FETCH_ASSOC 取回資料
     * @return mixed
     */
    public function execute($sql, $simple = false): mixed
    {
        if (!$this->conn) {
            $this->getConnection();
        }
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $simple
                ? $stmt->fetchAll(\PDO::FETCH_ASSOC)
                : $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "<p>" . $e->getMessage() . "</p>";
            exit();
        }
    }

    /**
     * 找出一行/多行資料
     *
     * @param string $table             資料表
     * @param string $attr              欄位名稱
     * @param string $target            where 後面放的字
     * @param boolean $returnResult     是否返回結果，如果設為否，則回傳 true/false；
     *                                  如果設為是，則回傳結果。
     * @param boolean $partialMatch     是否部分匹配
     * @return mixed
     */
    public function findExistRow(
        $table,
        $attr,
        $target,
        $returnResult = false,
        $partialMatch = false
    ): mixed {
        if (!$this->conn) {
            $this->getConnection();
        }
        try {
            $result = [];
            if (in_array($table, ["user_info"])) {
                $sql = "select * from user_info where user_id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam("id", $target, \PDO::PARAM_STR);
                $stmt->execute();
            } else {
                if (!$partialMatch) {
                    $sql = "select * from `$table` where `$attr` = :target;";
                } else {
                    $sql = "select * from `$table` where `$attr` like concat('%', :target, '%');";
                }
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam("target", $target, \PDO::PARAM_STR);
                $stmt->execute();
            }
            if ($returnResult) {
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            if (!$returnResult) {
                $result = $stmt->rowCount() !== 0 ? true : false;
            }
            return $result;
        } catch (PDOException $e) {
            echo "<p>" . $e->getMessage() . "</p>";
            exit();
        }
    }

    /**
     * 插入一行新資料列
     *
     * @param string $table         資料表名稱
     * @param array $insertArr     插入資料的串列
     * @param string $colArr        插入資料的欄位名稱
     * @return void
     */
    public function insertOneRow($table, $insertArr, $colArr)
    {
        if (!$this->conn) {
            $this->getConnection();
        }
        try {
            $colStr = $this->concatArr($colArr);
            $insertStr = $this->concatArr($insertArr, true);
            $sql = "insert into `{$table}` ({$colStr}) values ({$insertStr})";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<p>" . $e->getMessage() . "</p>";
            exit();
        }
    }

    /**
     * 將向量組合成 'item' 或是 `item`
     *
     * @param array $Arr       輸入向量
     * @param boolean $isStr    是不是字串
     * @return string           回傳字串
     */
    public function concatArr($Arr, $isStr = false): string
    {
        $str = "";
        $symbol = "`";
        if ($isStr) {
            $symbol = "'";
        }
        foreach ($Arr as $item) {
            $str .= "{$symbol}{$item}{$symbol}, ";
        }
        $str = substr($str, 0, -2);
        return $str;
    }

    /**
     * 註冊一名讀者
     *
     * @param array $insertArr 註冊資料向量
     * @return bool             回傳 true/false
     */
    public function register($insertArr): bool
    {
        $result = true;
        $colArr = ["user_nickname", "user_pass", "user_email", "user_gender"];
        $arrangedArr = [
            $insertArr["nickname"],
            $insertArr["pass"],
            $insertArr["email"],
            $insertArr["gender"],
        ];
        if (
            !$this->findExistRow("user_info", "user_email", $insertArr["email"])
        ) {
            $this->insertOneRow("user_info", $arrangedArr, $colArr);
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * 認證帳號
     *
     * @param string $userEmail 使用者 Email
     * @param string $userPass  使用者密碼
     * @return bool
     */
    public function auth($user, $pass): bool
    {
        if (is_numeric($user)) {
            $row = $this->findExistRow("user_info", "user_id", $user, true);
        } else {
            $row = $this->findExistRow("user_info", "user_email", $user, true);
        }

        if (count($row) == 0) {
            $auth = false;
            return $auth;
        }

        $row = $row[0];
        $pass = md5($row["user_salt"] . $pass);
        $auth =
            ($user == $row["user_id"]) & ($pass == $row["user_pass"])
                ? true
                : false;
        return $auth;
    }
}
