<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table            = 'leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['landing_page_id', 'name', 'email', 'phone', 'message', 'status'];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    // "Archiving" a lead is a soft delete: delete($id) stamps archived_at and the
    // row is automatically excluded from every model query (the leads list and the
    // API). A NULL archived_at means the lead is active/visible.
    protected $useSoftDeletes = true;
    protected $deletedField   = 'archived_at';

    public function search(string $term)
    {
        return $this->groupStart()
            ->like('name', $term)
            ->orLike('email', $term)
            ->groupEnd();
    }

    public function filterByLandingPage(int $id)
    {
        return $this->where('landing_page_id', $id);
    }
}
