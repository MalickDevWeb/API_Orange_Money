<?php
namespace App\Repositories;
use App\Interfaces\Repositories\CompteRepositoryInterface;
use App\Models\Compte;

class CompteRepository implements CompteRepositoryInterface
{

    public  function getAll(): array {
        return Compte::withTrashed()->get()->toArray();
    }


   public  function delete($id) {
      $compte = Compte::find($id);
      if ($compte) {
          $compte->delete();
          return true;
      }
      return false;
    }
    public  function create (array $data) {
      return Compte::create($data);
    }

    public  function update($id, array $data) {
      $compte = Compte::find($id);
      if ($compte) {
          $compte->update($data);
          return $compte;
      }
      return false;
    }

    public  function getById($id) {
      return Compte::withTrashed()->find($id);
    }
    public function restore($id) {
        $compte = Compte::withTrashed()->find($id);
        if ($compte) {
            $compte->restore();
            return $compte;
        }
        return false;
    }

    public function forceDelete($id) {
        $compte = Compte::withTrashed()->find($id);
        if ($compte) {
            $compte->forceDelete();
            return true;
        }
        return false;
    }
}
