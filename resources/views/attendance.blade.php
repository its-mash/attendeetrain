<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
</head>
<body>
    <div align="center">
        <!-- {{$data['src']}} -->
        <div> 
            <h2>{{$data['courseCode']}}-{{$data['section']}}</h2>

        </div>
        <div>
            {{$data["key"]}}
        </div>
        <img src="{{$data['src']}}" title="Link to Google.com" />

        <div>
            <font id ="counter" size="7">0</font>
        </div>

    </div>
    <script>
        function update(){
            $.ajax({
                type: "POST",
                url: "/getcount",
                data: {
                    "_token": "{{ csrf_token() }}", 
                    "key":"{{$data['key']}}"
                },
                  
            })
            .done(function(o) {
                // console.log(o);
                $('#counter').text(o);
             });
        }
        $(document).ready(function() {
            
            setInterval(update,500);
        })
    </script>
</body>
</html>
