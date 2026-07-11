@extends('layouts.app')

@section('title', 'Change Password')
@section('subtitle', 'Update your password to continue using the office portal.')

@section('content')
    <section class="card" style="max-width: 540px;">
        <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="form-grid" style="grid-template-columns: 1fr;">
                <div>
                    <label class="field-label" for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input-field">
                </div>

                <div>
                    <label class="field-label" for="password">New Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input-field">
                </div>

                <div>
                    <label class="field-label" for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input-field">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-primary">Update Password</button>
            </div>
        </form>
    </section>
@endsection
