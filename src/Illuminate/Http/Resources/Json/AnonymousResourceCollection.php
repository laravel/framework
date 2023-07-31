<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

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
    public $pagination;
    /**
     * @var string
     */
    public $base_name;

    /**
     * @var array
     */
    public $queries;

    /**
     * Indicates if the collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = false;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param  string  $collects
     * @return void
     */
    public function __construct($resource, $collects)
    {
        $this->getResourceName($collects);

        $this->getQueries();

        $this->resolvePagination($resource);

        $this->collects = $collects;

        parent::__construct($resource);
    }

    /**
     * @param $collects
     * @return void
     */
    public function getResourceName($collects):void{
        $this->base_name =  Str::of(class_basename($collects))
            ->replace('Resource','',false)
            ->plural()
            ->ucfirst()
            ->value();
    }

    /**
     * @return void
     */
    private function getQueries():void{
        $this->queries = array();
        if(isset($_SERVER['QUERY_STRING'])){
            parse_str($_SERVER['QUERY_STRING'], $this->queries);
            if(isset($this->queries['page']))
                unset($this->queries['page']);
        }
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
                'nextPageUrl' => $this->handelQueryInPagination($resource->nextPageUrl()),
                'prevPageUrl' => $this->handelQueryInPagination($resource->previousPageUrl()),
                'currentPage' => $resource->currentPage(),
                'lastPage' => $resource->lastPage(),
            ];
        }
        elseif ($resource instanceof Paginator){
            $this->pagination = [
                'nextPageUrl' => $this->handelQueryInPagination($resource->nextPageUrl()),
                'prevPageUrl' => $this->handelQueryInPagination($resource->previousPageUrl()),
            ];
        }
        else{
            $this->pagination =[];
        }
    }

    /**
     * @param $url
     * @return string
     */
    private function handelQueryInPagination($url):string{
        if ($this->queries && $url)
            return $url . '&' . http_build_query($this->queries , '', '&amp;');
        elseif ($url)
            return $url;
        else
            return '';
    }


    /**
     * @param $request
     * @return array
     */
    public function toArray($request):array
    {
        $data[$this->base_name] = $this->collection;
        if(!empty($this->pagination)){
            $data['pagination'] = $this->pagination;
        }
        return $data;
    }
}
