<label>
    Name
    <input type="text" name="name" value="{{ old('name', $admin?->name) }}" required>
</label>

<label>
    Email
    <input type="email" name="email" value="{{ old('email', $admin?->email) }}" required>
</label>

<label>
    Password
    <input type="password" name="password" @if (! $admin) required @endif>
    @if ($admin)
        <span class="muted">Leave blank to keep the current password.</span>
    @endif
</label>
