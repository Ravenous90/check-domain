<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainCheckResource;
use App\Models\Domain;
use App\Models\DomainCheck;
use Illuminate\Http\Request;

class DomainCheckController extends Controller
{
    public function index(Request $request, Domain $domain)
    {
        $checks = $domain->checks()->with('domain')->orderBy('id')->get();

        return DomainCheckResource::collection($checks);
    }

    public function store(Request $request, Domain $domain)
    {
        $data = $this->validated($request);
        $data['domain_id'] = $domain->id;
        $data['method'] = strtoupper($data['method']);
        if (($data['path'] ?? '/') === '') {
            $data['path'] = '/';
        }
        if ($data['path'][0] !== '/') {
            $data['path'] = '/'.$data['path'];
        }
        $data['next_run_at'] = now();

        $check = DomainCheck::query()->create($data);

        return (new DomainCheckResource($check->load('domain')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, DomainCheck $check)
    {
        $data = $this->validated($request, partial: true);
        if (isset($data['method'])) {
            $data['method'] = strtoupper($data['method']);
        }
        if (array_key_exists('path', $data)) {
            if ($data['path'] === '') {
                $data['path'] = '/';
            } elseif ($data['path'][0] !== '/') {
                $data['path'] = '/'.$data['path'];
            }
        }
        $check->update($data);

        return new DomainCheckResource($check->fresh()->load('domain'));
    }

    public function destroy(Request $request, DomainCheck $check)
    {
        $check->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'path' => [$partial ? 'sometimes' : 'required', 'string', 'max:2048'],
            'method' => [$partial ? 'sometimes' : 'required', 'in:GET,HEAD,get,head'],
            'interval_seconds' => [$partial ? 'sometimes' : 'required', 'integer', 'min:60', 'max:86400'],
            'timeout_seconds' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return $request->validate($rules);
    }
}
