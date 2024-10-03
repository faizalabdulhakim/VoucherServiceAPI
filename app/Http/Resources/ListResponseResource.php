<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResponseResource extends JsonResource
{
    /**
     * @var int
     */
    private $status;
    private $message;

    public function __construct($resource, $status, $message)
    {
        $this->status = $status;
        $this->message = $message;

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->items(),
            'meta' => [
                'firstPage' => $this->firstItem(),
                'lastPage' => $this->lastPage(),
                'currentPage' => $this->currentPage(),
                'totalData' => $this->total(),
                'perPage' => $this->perPage(),
                'nextPage' => $this->nextPageUrl(),
                'prevPage' => $this->previousPageUrl(),
            ],
        ];
    }
}
