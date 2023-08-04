<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class AnonymousResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * @var array
     */
    public array $pagination;

    /**
     * @var array
     */
    public array $queries;

    /**
     * Indicates if the collection keys should be preserved.
     *
     * @var bool
     */
    public bool $preserveKeys = false;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param string $collects
     * @return void
     */
    public function __construct($resource, string $collects)
    {
        $this->queries = \request()->except('page');

        $this->resolvePagination($resource);

        $this->collects = $collects;

        parent::__construct($resource);
    }

    /**
     * @param $resource
     * @return void
     */
    public function resolvePagination($resource):void{
        if($resource instanceof LengthAwarePaginator){
            $this->pagination = [
                'total' => $resource->total(),
                'count' => $resource->count(),
                'perPage' => $resource->perPage(),
                'options' => $resource->getOptions(),
                'queries'=> $this->queries,
                'nextPageUrl' => $this->handelQueryInPagination($resource->nextPageUrl()??''),
                'prevPageUrl' => $this->handelQueryInPagination($resource->previousPageUrl()??''),
                'currentPage' => $resource->currentPage(),
                'lastPage' => $resource->lastPage(),
            ];
        }
        elseif ($resource instanceof Paginator){
            $this->pagination = [
                'total' => $resource->total(),
                'nextPageUrl' => $this->handelQueryInPagination($resource->nextPageUrl()??''),
                'prevPageUrl' => $this->handelQueryInPagination($resource->previousPageUrl()??''),
                'currentPage' => $resource->currentPage(),
                'perPage' => $resource->perPage(),
            ];
        }
        else{
            $this->pagination =[];
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function handelQueryInPagination(string $url=''):string{
        return ($this->queries && $url)?
            $url . '&' . http_build_query($this->queries , '', '&amp;') : $url;
    }


    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request):array
    {
        if(empty($this->pagination)){
            $data = $this->collection->map->toArray($request)->all();
        }else{
            $data['data'] = $this->collection;
            $data['paginate'] = $this->pagination;
        }
        return $data;
    }
}
