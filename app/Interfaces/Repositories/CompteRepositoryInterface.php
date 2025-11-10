<?php

namespace App\Interfaces\Repositories;
use App\Interfaces\RepositoryInterface;
interface CompteRepositoryInterface extends RepositoryInterface {
    public function restore($id);
    public function forceDelete($id);
}
