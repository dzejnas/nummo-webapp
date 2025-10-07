<?php
class UserDAO {
    private $pdo;
    public function __construct(PDO $pdo){ $this->pdo = $pdo; }
    public function createUser($name,$email,$passwordHash){
        $stmt = $this->pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
        return $stmt->execute([$name,$email,$passwordHash]);
    }
    public function getByEmail($email){
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
