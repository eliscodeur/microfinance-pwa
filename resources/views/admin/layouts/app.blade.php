<!DOCTYPE html>
<html>
<head>
    <title>NANA CONSULTING</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="/dashboard">Admin</a>

    <div>
        <a href="/agents/" class="btn btn-light btn-sm">Agents</a>
        <a href="/clients" class="btn btn-light btn-sm">Clients</a>
    </div>
</nav>

<div class="container mt-4">
    @yield('content')
</div>

</body>
</html>