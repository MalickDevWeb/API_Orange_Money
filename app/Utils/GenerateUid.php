<?php
namespace App\Utils;
use Illuminate\Support\Str;

trait GenerateUid {
     public static function bootGenerateUid()
     {
         static::creating(function ($model) {
             if (empty($model->id)) {
                 $model->id = (string) Str::uuid();
             }
         });
     }
 }

