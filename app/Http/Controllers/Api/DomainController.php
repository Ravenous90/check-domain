<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use App\Support\Hostname;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        $query = Domain::query()->with('checks');

        if (! $request->user()->isSuperuser()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return DomainResource::collection($query->orderBy('hostname')->paginate(50));
    }

    public function store(Request $request)
    {
        $ownerId = $request->user()->id;
        if ($request->user()->isSuperuser() && $request->filled('user_id')) {
            $request->validate(['user_id' => ['exists:users,id']]);
            $ownerId = $request->integer('user_id');
        }

        $request->merge([
            'hostname' => Hostname::normalize($request->input('hostname', '')),
        ]);

        $request->validate([
            'hostname' => [
                'required',
                'string',
                'max:253',
                Rule::unique('domains', 'hostname')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
        ]);

        $domain = Domain::query()->create([
            'user_id' => $ownerId,
            'hostname' => $request->string('hostname')->toString(),
        ]);

        return (new DomainResource($domain->load('checks')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Domain $domain)
    {
        $this->authorizeDomain($request, $domain);

        return new DomainResource($domain->load('checks'));
    }

    public function update(Request $request, Domain $domain)
    {
        $this->authorizeDomain($request, $domain);

        $targetUserId = $domain->user_id;
        if ($request->user()->isSuperuser() && $request->has('user_id')) {
            $request->validate(['user_id' => ['required', 'exists:users,id']]);
            $targetUserId = $request->integer('user_id');
        }

        $payload = [];
        if ($request->has('hostname')) {
            $request->merge([
                'hostname' => Hostname::normalize($request->input('hostname')),
            ]);
            $request->validate([
                'hostname' => [
                    'required',
                    'string',
                    'max:253',
                    Rule::unique('domains', 'hostname')
                        ->where(fn ($q) => $q->where('user_id', $targetUserId))
                        ->ignore($domain->id),
                ],
            ]);
            $payload['hostname'] = $request->string('hostname')->toString();
        }

        if ($request->user()->isSuperuser() && $request->has('user_id')) {
            $payload['user_id'] = $targetUserId;
        }

        if ($payload !== []) {
            $domain->update($payload);
        }

        return new DomainResource($domain->fresh()->load('checks'));
    }

    public function destroy(Request $request, Domain $domain)
    {
        $this->authorizeDomain($request, $domain);
        $domain->delete();

        return response()->json(null, 204);
    }

    private function authorizeDomain(Request $request, Domain $domain): void
    {
        if (! $request->user()->isSuperuser() && $domain->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
