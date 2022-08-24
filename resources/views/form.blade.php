<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Here</title>
</head>
<body>

@if(session()->has('responseMessage'))
    <div class="alert alert-success alert-dismissible fade in">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session()->get('responseMessage') }}
    </div>
@endif
    <h1>Please Enter Your Details Here </h1>
    <form method="post" action="http://127.0.0.1:8000/api/create">
        <p>Please Enter Your Name::</p>
        <input type="text" name="name">
        <br>
        <p>Please Enter Your Email</p>
        <input type="email" name="email">
        <br>
        <p>Please Enter Your Password::</p>
        <input type="text" name="password">
        <br>
        <br>
        <button type="submit">Submit Here </button>
    </form>
</body>
</html>