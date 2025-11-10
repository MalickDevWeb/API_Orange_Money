<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\DTOs\UserDTO;
use App\Interfaces\Auth\AuthInterfaceRepository;
use Illuminate\Support\Facades\Log;


use App\Interfaces\Auth\AuthInterfaceService;

class AuthService implements AuthInterfaceService
{

        protected  AuthInterfaceRepository $authRepo;
        public function __construct(AuthInterfaceRepository $authRepo) {
               $this->authRepo = $authRepo;
        }


   public function register(array $data): User
   {
       try {
           $userData = UserDTO::fromArray($data);
           return $this->authRepo->register($userData);
       } catch (Exception $e) {
           Log::error('Erreur lors de la création de l’utilisateur : ' . $e->getMessage(), [
               'trace' => $e->getTraceAsString(),
               'data' => $data,
           ]);
           throw $e; // Relancer l'exception pour une gestion uniforme
       }
   }

    public function login(array $credentials)
    {

    }

    public function logout()
    {

    }

     public function user()
     {

     }

}
