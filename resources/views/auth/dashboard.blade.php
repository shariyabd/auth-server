@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="card dashboard-card">
        <div class="user-info">
            <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <div style="font-weight:600;">{{ $user->name }}</div>
                <div style="font-size:0.875rem;color:#6b7280;">{{ $user->email }}</div>
            </div>
        </div>

        <h1 style="text-align:left;font-size:1.125rem;margin-bottom:0.5rem;">SSO Dashboard</h1>
        <p class="subtitle" style="text-align:left;">Manage your single sign-on sessions across connected applications.</p>

        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-value">{{ $activeTokens }}</div>
                <div class="stat-label">Active Sessions</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">2</div>
                <div class="stat-label">Connected Apps</div>
            </div>
        </div>

        <h2 style="font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;">Connected Applications</h2>
        <div class="stat-box" style="text-align:left;margin-bottom:0.75rem;">
            <div style="font-weight:600;">🛒 Ecommerce App</div>
            <div style="font-size:0.75rem;color:#6b7280;">{{ config('services.sso.ecommerce_url') }}</div>
        </div>
        <div class="stat-box" style="text-align:left;margin-bottom:1rem;">
            <div style="font-weight:600;">🍔 Foodpanda App</div>
            <div style="font-size:0.75rem;color:#6b7280;">{{ config('services.sso.foodpanda_url') }}</div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-danger">Logout from All Apps</button>
        </form>
    </div>
@endsection