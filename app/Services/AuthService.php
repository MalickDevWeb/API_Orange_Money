<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Interfaces\Auth\AuthInterfaceRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;


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
           // Hash du mot de passe avant enregistrement
           $data['password'] = Hash::make($data['password']);
           return $this->authRepo->register($data);
       } catch (Exception $e) {
           Log::error('Erreur lors de la création de l\'utilisateur : ' . $e->getMessage(), [
               'trace' => $e->getTraceAsString(),
               'data' => $data,
           ]);
           throw $e; // Relancer l'exception pour une gestion uniforme
       }
   }

    public function login(array $credentials): User
    {
        try {
            return $this->authRepo->login($credentials);
        } catch (Exception $e) {
            Log::error('Erreur lors de la connexion de l\'utilisateur : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'credentials' => $credentials,
            ]);
            throw $e;
        }
    }

    public function logout(): void
    {
        try {
            $this->authRepo->logout();
        } catch (Exception $e) {
            Log::error('Erreur lors de la déconnexion de l\'utilisateur : ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

     public function user(): User
     {
         try {
             return $this->authRepo->user();
         } catch (Exception $e) {
             Log::error('Erreur lors de la récupération de l\'utilisateur : ' . $e->getMessage(), [
                 'trace' => $e->getTraceAsString(),
             ]);
             throw $e;
         }
     }

}
