<?php

namespace App\Repositories\LinkAds;

use App\Repositories\RepositoryInterface;

interface LinkAdsRepositoryInterface extends RepositoryInterface
{
    public function getAdsNewest($number = 3);
}