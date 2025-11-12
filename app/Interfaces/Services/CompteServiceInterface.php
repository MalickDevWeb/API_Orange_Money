<?php
namespace App\Interfaces\Services;
use App\Interfaces\ServiceInterface;
interface CompteServiceInterface extends ServiceInterface {
    public function restore(string $id);
    public function forceDelete(string $id);
}
