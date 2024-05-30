<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KonselorResource extends JsonResource
{

    public $isSuccess;
    public $message;

    public function __construct($isSuccess, $message, $resource)
    {
        parent::__construct($resource);
        $this->isSuccess = $isSuccess;
        $this->message = $message;
    }

    public function toArray($request)
    {
        return [
            'success' => $this->isSuccess,
            'message' => $this->message,
            'data' => $this->resource
        ];
    }
}