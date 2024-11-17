<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 11:04 AM
 */

namespace App\Repositories\AdsTracking;

use App\Repositories\RepositoryInterface;

interface AdsTrackingRepositoryInterface extends RepositoryInterface
{
    public function filter($request,$dug_store_id, $limit);

}