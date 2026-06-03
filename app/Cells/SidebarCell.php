<?php

namespace App\Cells;

use CodeIgniter\View\Cells\Cell;

class SidebarCell extends Cell
{
    public string $active = '';
    protected string $view = 'sidebar';

    public function render(): string
    {
        return $this->view($this->view, ['active' => $this->active]);
    }
}
