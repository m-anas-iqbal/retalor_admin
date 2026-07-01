<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        Plan::create($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan created.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name'], $plan);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $plan->update($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan updated.');
    }

    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->withErrors(['plan' => 'This plan has subscriptions and cannot be deleted.']);
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plan deleted.');
    }

    private function uniqueSlug(string $name, ?Plan $plan = null): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;
        $counter = 2;

        $query = Plan::query();

        if ($plan) {
            $query->whereKeyNot($plan->id);
        }

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
