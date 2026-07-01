@php($plan = $plan ?? null)

<label class="field">
    <span>Name</span>
    <input name="name" type="text" value="{{ old('name', $plan?->name) }}" required>
</label>

<label class="field">
    <span>Slug</span>
    <input name="slug" type="text" value="{{ old('slug', $plan?->slug) }}" placeholder="auto-generated if empty">
</label>

<label class="field">
    <span>Description</span>
    <textarea name="description" rows="4">{{ old('description', $plan?->description) }}</textarea>
</label>

<div class="grid two">
    <label class="field">
        <span>Price</span>
        <input name="price" type="number" min="0" step="0.01" value="{{ old('price', $plan?->price) }}" required>
    </label>

    <label class="field">
        <span>Duration Days</span>
        <input name="duration_days" type="number" min="1" value="{{ old('duration_days', $plan?->duration_days ?? 30) }}" required>
    </label>
</div>

<div class="grid two">
    <label class="field">
        <span>Max Shops</span>
        <input name="max_shops" type="number" min="1" value="{{ old('max_shops', $plan?->max_shops ?? 1) }}" required>
    </label>

    <label class="field">
        <span>Max Users</span>
        <input name="max_users" type="number" min="1" value="{{ old('max_users', $plan?->max_users ?? 1) }}" required>
    </label>
</div>

<label class="checkbox">
    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $plan?->is_active ?? true))>
    <span>Active plan</span>
</label>
