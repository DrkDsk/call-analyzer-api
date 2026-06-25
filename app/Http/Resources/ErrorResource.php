<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class ErrorResource extends JsonResource
{
    public function __construct(
        string $message,
        protected int $status = Response::HTTP_BAD_REQUEST,
        protected array $errors = [],
    ) {
        parent::__construct([
            'message' => $message,
        ]);
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => $this->resource['message'],
            'errors' => $this->errors,
        ];
    }

    public function withResponse($request, $response): void
    {
        $response->setStatusCode($this->status);
    }
}
