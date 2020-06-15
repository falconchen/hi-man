<?php
namespace App\Helper;

use Illuminate\Database\Eloquent\Builder;

trait ActionHelper {

    /**
     * print SQL
     *
     * @param Builder $builder
     * @return string
     */
    function getSQL(Builder $builder) {
        $sql = $builder->toSql();
        foreach ( $builder->getBindings() as $binding ) {
          $value = is_numeric($binding) ? $binding : "'".$binding."'";
          $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        return $sql;
      }
      
}