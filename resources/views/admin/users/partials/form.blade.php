<label>
    Name
    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required>
</label>

<label>
    Email
    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
</label>

<label>
    Password
    <input type="password" name="password" @if (! $user) required @endif>
    @if ($user)
        <span class="muted">Leave blank to keep the current password.</span>
    @endif
</label>
