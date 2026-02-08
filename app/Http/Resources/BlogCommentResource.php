<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at->toISOString(),
            'author' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'avatar' => $this->student->avatar ?? null,
            ],
        ] + ($request->user() ? [
            'is_author' => $this->student_id === $request->user()->id,
        ] : []);
    }
}
