<?php

namespace App\Middleware;

class PaginationMiddleware
{

    public function __invoke($request, $response, $next)
    {
        \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($request) {
            return $request->getParam('page');
        });

        return $next($request, $response);
    }
}
