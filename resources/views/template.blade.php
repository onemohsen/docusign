<!DOCTYPE html>
<html lang="en">

<head>
    <title>Laravel Docusign Integration Example - ItSlutionStuff.com</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container py-5">
        <form action="{{ route('docusign.sign-template') }}" method="post">
            @csrf
            <div class="form-group">
                <label for="template-name">Template Name</label>
                <input name="template" id="template-name" type="text" class="form-control"
                    placeholder="template name in docusign" required>
                @error('template')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="name">Signer Name</label>
                <input name="name" type="text" id="name" class="form-control" placeholder="full name"
                    required>
                @error('name')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="email">Signer Email address</label>
                <input name="email" id="email" type="email" class="form-control" placeholder="name@example.com"
                    required>
                @error('email')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary mb-2">Submit</button>
        </form>
    </div>
</body>

</html>
