<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Send magic link to the mail</title>
    <style>
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .error {
            color: red;
            margin-top: 5px;
        }
        .success {
            color: green;
            margin-top: 5px;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Magic Link</h1>
        
        @if (session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('magiclink.request') }}">
            @csrf
            <div class="form-group">
                <label for="email">Enter your email:</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit">Send Magic Link</button>
        </form>
    </div>
</body>
</html>