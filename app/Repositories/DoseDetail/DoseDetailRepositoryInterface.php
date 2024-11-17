<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */


namespace App\Repositories\DoseDetail;

use App\Repositories\RepositoryInterface;

interface DoseDetailRepositoryInterface extends RepositoryInterface
{
    public function getDetailById($dose_id);
}