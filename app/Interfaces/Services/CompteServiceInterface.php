<?php
namespace App\Interfaces\Services;
use App\Interfaces\ServiceInterface;
interface CompteServiceInterface extends ServiceInterface {
    public function restore($id);
    public function forceDelete($id);
}
