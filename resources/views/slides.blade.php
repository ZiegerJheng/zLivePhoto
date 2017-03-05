<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Superslides - A fullscreen slider for jQuery</title>
  <link rel="stylesheet" href="css/superslides.css">
  <style type="text/css">
    .container {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 30px;
      padding: 20px;
      background-color: rgba(128, 128, 128, 0.6);
      color: white;
    }
    .container h1 {
      position: relative;
      margin: 0;
      padding: 0;
      left: 10px;
      font-size: 40px;
    }
    .container p {
      position: relative;
      margin: 0;
      padding: 0;
      left: 10px;
      font-size: 36px;
    }
  </style>
</head>
<body>
  <div id="slides">
    <ul class="slides-container">
    </ul>
  </div>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.animate-enhanced.min.js"></script>
  <script src="js/jquery.superslides.js" type="text/javascript" charset="utf-8"></script>
  <script>
    $( document ).ready(function() {
        var photoIDs = [];

        function getPhotoFeed( callback ){
            console.log( "getPhotoFeed start" );

            $.getJSON("/photoFeeds", function(json) {
                if (json.feeds.length > 0) {
                    console.log( json.feeds.length + " photos got" );

                    $.each(json.feeds, function() {
                        if(photoIDs.indexOf(this['id']) == -1){
                            photoIDs.push(this['id']);

                            var slide = '<li><img src="' + this['photoUrl'] + '" /><div class="container"><h1>' + this['userName'] + '</h1><p>' + this['message'] + '</p></div></li>';
                            $('.slides-container').append( slide );

                            console.log("1 photo appened: " + this['id']);
                        }
                    });
                    
                }
                callback();
            }).complete(function() {
                //callback();
            });
        }

        function reqularFetchPhoto(freq) {
            setTimeout(function() {
                    console.log( "reqular timeout setted" );
                    getPhotoFeed(function() {
                        $('#slides').superslides('update');
                        console.log( "superlides updated" );
                        
                        reqularFetchPhoto(freq);
                    });
                },
                freq
            );
        }

        function initSlide() {
            getPhotoFeed(function() {
                $('#slides').superslides({
                  animation: 'fade',
                  play: '3000',
                  pagination: false
                });
                console.log( "superslides ready" );
                
                $( document ).bind('animated.slides', function() {
                    numberofslides = $('#slides').superslides('size');
                    currentslide = $('#slides').superslides('current');
                    console.log( numberofslides + "<>" + currentslide );
                });
            });
        }

        initSlide();
        reqularFetchPhoto(10000);
    
    });

  </script>
</body>
</html>
