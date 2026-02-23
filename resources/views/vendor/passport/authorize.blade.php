
@extends('layouts.app')

@section('title', 'Authorize Application')

@section('content')
    <div class="card">
        <h1>Authorization Request</h1>
        <p class="subtitle"><strong>{{ $client->name }}</strong> is requesting access to your account.</p>

        @if (count($scopes) > 0)
            <h2 style="font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;">This application will be able to:</h2>
            <ul class="scope-list">
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="btn-group">
            <!-- Approve -->
            <form method="POST" action="{{ route('passport.authorizations.approve') }}" style="flex:1;">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn">Authorize</button>
            </form>

            <!-- Deny -->
            <form method="POST" action="{{ route('passport.authorizations.deny') }}" style="flex:1;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-outline">Deny</button>
            </form>
        </div>
    </div>
@endsection