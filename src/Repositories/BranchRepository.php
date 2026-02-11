<?php

namespace ADMS\Repositories;

use ADMS\Database\Database;
use ADMS\Models\Branch;

class BranchRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById($id)
    {
        $stmt = $this->db->query(
            "SELECT * FROM branches WHERE id = ?",
            [$id]
        );
        $data = $stmt->fetch();
        return $data ? new Branch($data) : null;
    }

    public function findByCode($code)
    {
        $stmt = $this->db->query(
            "SELECT * FROM branches WHERE code = ?",
            [$code]
        );
        $data = $stmt->fetch();
        return $data ? new Branch($data) : null;
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM branches ORDER BY name");
        
        $branches = [];
        while ($data = $stmt->fetch()) {
            $branches[] = new Branch($data);
        }
        return $branches;
    }

    public function create($data)
    {
        $sql = "INSERT INTO branches (name, code, location) VALUES (?, ?, ?)";
        
        $this->db->execute($sql, [
            $data['name'],
            $data['code'],
            $data['location'] ?? null
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        $fields[] = "updated_at = datetime('now')";
        $params[] = $id;

        $sql = "UPDATE branches SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->execute($sql, $params);

        return $this->findById($id);
    }

    public function delete($id)
    {
        return $this->db->execute("DELETE FROM branches WHERE id = ?", [$id]);
    }
}
