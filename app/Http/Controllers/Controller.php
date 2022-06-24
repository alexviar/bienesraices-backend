<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  protected function applyFilters($query, $queryArgs)
  {
  }

  protected function buildPaginatedResponseData($meta, $records)
  {
    return [
      "meta" => $meta,
      "records" => $records
    ];
  }

  protected function buildResponse($query, $queryArgs)
  {

    $this->applyFilters($query, $queryArgs);

    $total = $query->count();
    $current = Arr::get($queryArgs, "page.current", 1);
    $size = Arr::get($queryArgs, "page.size", $total);
    
    $query->limit($size);
    $query->offset(($current - 1) * $size);

    $meta = ["totalRecords" => $total, "currentPage" => $current];
    if ($current * $size < $total) $meta["nextPage"] = $current + 1;
    if ($current > 1) $meta["prevPage"] = $current - 1;

    return $this->buildPaginatedResponseData($meta, $query->get());
  }
}
