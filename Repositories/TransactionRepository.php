<?php

namespace App\Repositories;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Models\Transaction;

class TransactionRepository implements TransactionRepositoryInterface
{

   public function getAll(): array {
    return Transaction::all()->toArray();

   }

   public function getById($id) {
    return Transaction::find($id);
     }


   public function create (array $data){
       return Transaction::create($data);
   }

   public function update($id, array $data) {
       $transaction = Transaction::find($id);
       if ($transaction) {
           $transaction->update($data);
           return $transaction;
       }
       return false;
   }

   public function delete($id) {
       $transaction = Transaction::find($id);
       if ($transaction) {
           $transaction->delete();
           return true;
       }
       return false;
   }
}
