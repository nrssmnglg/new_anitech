<?php

namespace App\Http\Resources\Farmer;

use App\Models\Advisory;
use App\Models\Query;
use App\Models\QueryResponse;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AttachmentResource extends JsonResource
{
    public static function serialize(Attachment $attachment, Query|Advisory|null $query = null, QueryResponse|null $response = null): array
    {
        $filename = (string) ($attachment->original_name ?: basename((string) $attachment->file_path));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) ($attachment->mime_type ?? ''));
        $isImage = Str::startsWith($mimeType, 'image/')
            || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
        $canPreview = $isImage || $mimeType === 'application/pdf' || $extension === 'pdf';
        $url = null;

        if ($query instanceof Advisory) {
            $routeKey = $query->slug ?: $query->getKey();
            $url = route('api.farmer.advisories.attachments.show', [
                'advisory' => $routeKey,
                'attachment' => $attachment->id,
            ]);
        } elseif ($query instanceof Query && $response instanceof QueryResponse) {
            $url = route('api.farmer.inquiries.responses.attachments.show', [
                'inquiry' => $query->getRouteKey(),
                'response' => $response->getKey(),
                'attachment' => $attachment->id,
            ]);
        } elseif ($query instanceof Query) {
            $url = route('api.farmer.inquiries.attachments.show', [
                'inquiry' => $query->getRouteKey(),
                'attachment' => $attachment->id,
            ]);
        } else {
            $module = $attachment->module;

            if ($module instanceof Advisory) {
                $routeKey = $module->slug ?: $module->getKey();
                $url = route('api.farmer.advisories.attachments.show', [
                    'advisory' => $routeKey,
                    'attachment' => $attachment->id,
                ]);
            } elseif ($module instanceof Query) {
                $url = route('api.farmer.inquiries.attachments.show', [
                    'inquiry' => $module->getRouteKey(),
                    'attachment' => $attachment->id,
                ]);
            } elseif ($module instanceof QueryResponse) {
                $inquiry = $module->inquiry;

                if ($inquiry) {
                    $url = route('api.farmer.inquiries.responses.attachments.show', [
                        'inquiry' => $inquiry->getRouteKey(),
                        'response' => $module->getKey(),
                        'attachment' => $attachment->id,
                    ]);
                }
            }
        }

        return [
            'id' => $attachment->id,
            'name' => $filename,
            'path' => $attachment->file_path,
            'mime_type' => $attachment->mime_type,
            'url' => $url,
            'is_image' => $isImage,
            'can_preview' => $canPreview,
            'uploaded_at' => optional($attachment->uploaded_at)->toIso8601String(),
        ];
    }

    public function toArray(Request $request): array
    {
        return self::serialize($this->resource);
    }
}
